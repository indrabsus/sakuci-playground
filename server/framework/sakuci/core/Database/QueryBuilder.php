<?php

namespace Sakuci\Database;

/**
 * Query builder sederhana:
 *
 *   Post::where('status', 'published')->orderBy('id', 'desc')->limit(5)->get();
 *   Post::query()->where('title', 'like', '%php%')->paginate(10);
 */
class QueryBuilder
{
    protected array $columns  = ['*'];
    protected array $wheres   = [];
    protected array $bindings = [];
    protected array $orders   = [];
    protected array $joins    = [];
    protected ?string $groupBy = null;
    protected ?int $limit     = null;
    protected ?int $offset    = null;

    /**
     * Relasi yang akan dimuat lebih awal (eager loading).
     * Bentuk: ['comments' => ['nested' => ['user'], 'constraint' => ?Closure]]
     */
    protected array $eagerLoad = [];

    /** Dipakai oleh relasi untuk menentukan hasil banyak atau satu. */
    public bool $relationMany = true;

    public function __construct(
        protected string $table,
        protected ?string $model = null
    ) {
    }

    /*
    |----------------------------------------------------------------------
    | Penyusun query
    |----------------------------------------------------------------------
    */

    public function select(string|array $columns): static
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        return $this;
    }

    /**
     * where('id', 1) | where('umur', '>=', 17) | where(['a' => 1, 'b' => 2])
     */
    public function where(string|array|\Closure $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): static
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val, $boolean);
            }

            return $this;
        }

        // Grup kondisi bersarang: where(function ($q) { ... })
        if ($column instanceof \Closure) {
            $nested = new static($this->table, $this->model);
            $column($nested);

            [$sql, $bindings] = $nested->whereParts();

            if ($sql !== '') {
                $this->wheres[]  = [$boolean, '(' . $sql . ')'];
                $this->bindings  = array_merge($this->bindings, $bindings);
            }

            return $this;
        }

        if (func_num_args() === 2 || ($value === null && ! in_array(strtolower((string) $operator), ['=', '!=', '<>', '>', '<', '>=', '<=', 'like', 'not like'], true))) {
            $value    = $operator;
            $operator = '=';
        }

        $this->wheres[]   = [$boolean, $this->wrap($column) . ' ' . strtoupper((string) $operator) . ' ?'];
        $this->bindings[] = $value;

        return $this;
    }

    public function orWhere(string|array|\Closure $column, mixed $operator = null, mixed $value = null): static
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    public function whereIn(string $column, array $values, string $boolean = 'AND', bool $not = false): static
    {
        if ($values === []) {
            $this->wheres[] = [$boolean, $not ? '1 = 1' : '1 = 0'];

            return $this;
        }

        $placeholders   = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = [$boolean, $this->wrap($column) . ($not ? ' NOT IN ' : ' IN ') . '(' . $placeholders . ')'];
        $this->bindings = array_merge($this->bindings, array_values($values));

        return $this;
    }

    public function whereNotIn(string $column, array $values): static
    {
        return $this->whereIn($column, $values, 'AND', true);
    }

    public function whereNull(string $column, string $boolean = 'AND'): static
    {
        $this->wheres[] = [$boolean, $this->wrap($column) . ' IS NULL'];

        return $this;
    }

    public function whereNotNull(string $column, string $boolean = 'AND'): static
    {
        $this->wheres[] = [$boolean, $this->wrap($column) . ' IS NOT NULL'];

        return $this;
    }

    public function whereLike(string $column, string $value): static
    {
        return $this->where($column, 'LIKE', $value);
    }

    public function whereBetween(string $column, array $range): static
    {
        $this->wheres[]   = ['AND', $this->wrap($column) . ' BETWEEN ? AND ?'];
        $this->bindings[] = $range[0];
        $this->bindings[] = $range[1];

        return $this;
    }

    public function join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): static
    {
        $this->joins[] = strtoupper($type) . ' JOIN ' . $this->wrap($table)
            . ' ON ' . $this->wrap($first) . ' ' . $operator . ' ' . $this->wrap($second);

        return $this;
    }

    public function leftJoin(string $table, string $first, string $operator, string $second): static
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    public function groupBy(string $column): static
    {
        $this->groupBy = $this->wrap($column);

        return $this;
    }

    /**
     * Muat relasi lebih awal supaya tidak terjadi query berulang (N+1).
     *
     *   Post::with('comments')->get();
     *   Post::with(['comments', 'user'])->get();
     *   Post::with('comments.user')->get();                      // bersarang
     *   Post::with('comments', fn ($q) => $q->latest())->get();  // dengan syarat
     *   Post::with(['comments' => fn ($q) => $q->limit(3)])->get();
     */
    public function with(string|array $relations, ?\Closure $constraint = null): static
    {
        if (is_string($relations)) {
            $relations = $constraint !== null ? [$relations => $constraint] : [$relations];
        }

        foreach ($relations as $key => $value) {
            $name       = is_int($key) ? $value : $key;
            $callback   = is_int($key) ? null : ($value instanceof \Closure ? $value : null);

            if (! is_string($name)) {
                continue;
            }

            // "comments.user" -> root "comments", sisanya dimuat bertingkat.
            [$root, $nested] = array_pad(explode('.', $name, 2), 2, null);

            $this->eagerLoad[$root] ??= ['nested' => [], 'constraint' => null];

            if ($nested !== null) {
                $this->eagerLoad[$root]['nested'][] = $nested;
            } elseif ($callback !== null) {
                $this->eagerLoad[$root]['constraint'] = $callback;
            }
        }

        return $this;
    }

    public function orderBy(string $column, string $direction = 'asc'): static
    {
        $direction     = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
        $this->orders[] = $this->wrap($column) . ' ' . $direction;

        return $this;
    }

    public function latest(string $column = 'id'): static
    {
        return $this->orderBy($column, 'desc');
    }

    public function oldest(string $column = 'id'): static
    {
        return $this->orderBy($column, 'asc');
    }

    public function limit(int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function take(int $limit): static
    {
        return $this->limit($limit);
    }

    public function offset(int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }

    public function skip(int $offset): static
    {
        return $this->offset($offset);
    }

    /*
    |----------------------------------------------------------------------
    | Eksekusi
    |----------------------------------------------------------------------
    */

    public function get(): array
    {
        $rows   = Connection::select($this->toSql(), $this->bindings);
        $models = $this->hydrate($rows);

        // Ambil semua relasi yang diminta with() â€” satu query per relasi,
        // bukan satu query per baris (inilah penangkal N+1).
        if ($this->eagerLoad !== [] && $models !== []) {
            $this->eagerLoadOn($models);
        }

        return $models;
    }

    public function first(): mixed
    {
        $rows = (clone $this)->limit(1)->get();

        return $rows[0] ?? null;
    }

    public function find(mixed $id, string $column = 'id'): mixed
    {
        return (clone $this)->where($column, '=', $id)->first();
    }

    public function value(string $column): mixed
    {
        $row = (clone $this)->select($column)->first();

        if ($row === null) {
            return null;
        }

        return is_array($row) ? ($row[$column] ?? null) : $row->{$column};
    }

    public function pluck(string $column, ?string $key = null): array
    {
        $result = [];

        foreach ($this->get() as $row) {
            $value = is_array($row) ? $row[$column] : $row->{$column};

            if ($key === null) {
                $result[] = $value;
            } else {
                $result[is_array($row) ? $row[$key] : $row->{$key}] = $value;
            }
        }

        return $result;
    }

    public function count(string $column = '*'): int
    {
        $query = clone $this;
        $query->columns = ['COUNT(' . ($column === '*' ? '*' : $this->wrap($column)) . ') AS aggregate'];
        $query->orders  = [];
        $query->limit   = null;
        $query->offset  = null;

        $row = Connection::selectOne($query->toSql(), $query->bindings);

        return (int) ($row['aggregate'] ?? 0);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    public function paginate(int $perPage = 15, ?int $page = null): Paginator
    {
        $page  = max(1, $page ?? (int) (request('page') ?: 1));
        $total = $this->count();

        $items = (clone $this)->limit($perPage)->offset(($page - 1) * $perPage)->get();

        return new Paginator($items, $total, $perPage, $page);
    }

    public function insert(array $data): string
    {
        $columns      = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $sql = 'INSERT INTO ' . $this->wrap($this->table)
            . ' (' . implode(', ', array_map([$this, 'wrap'], $columns)) . ')'
            . ' VALUES (' . $placeholders . ')';

        return Connection::insertGetId($sql, array_values($data));
    }

    public function update(array $data): int
    {
        if ($data === []) {
            return 0;
        }

        $sets = [];
        foreach (array_keys($data) as $column) {
            $sets[] = $this->wrap($column) . ' = ?';
        }

        [$whereSql, $whereBindings] = $this->whereParts();

        $sql = 'UPDATE ' . $this->wrap($this->table) . ' SET ' . implode(', ', $sets)
            . ($whereSql !== '' ? ' WHERE ' . $whereSql : '');

        return Connection::statement($sql, array_merge(array_values($data), $whereBindings));
    }

    public function delete(): int
    {
        [$whereSql, $whereBindings] = $this->whereParts();

        $sql = 'DELETE FROM ' . $this->wrap($this->table)
            . ($whereSql !== '' ? ' WHERE ' . $whereSql : '');

        return Connection::statement($sql, $whereBindings);
    }

    /*
    |----------------------------------------------------------------------
    | Penyusunan SQL
    |----------------------------------------------------------------------
    */

    public function toSql(): string
    {
        $sql = 'SELECT ' . implode(', ', array_map(function (string $column): string {
            return $column === '*' || str_contains($column, '(') ? $column : $this->wrap($column);
        }, $this->columns)) . ' FROM ' . $this->wrap($this->table);

        if ($this->joins) {
            $sql .= ' ' . implode(' ', $this->joins);
        }

        [$whereSql] = $this->whereParts();

        if ($whereSql !== '') {
            $sql .= ' WHERE ' . $whereSql;
        }

        if ($this->groupBy) {
            $sql .= ' GROUP BY ' . $this->groupBy;
        }

        if ($this->orders) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        if ($this->offset !== null) {
            $sql .= ' OFFSET ' . $this->offset;
        }

        return $sql;
    }

    public function getBindings(): array
    {
        return $this->bindings;
    }

    /** @return array{0: string, 1: array} */
    protected function whereParts(): array
    {
        $sql = '';

        foreach ($this->wheres as $index => [$boolean, $condition]) {
            $sql .= ($index === 0 ? '' : ' ' . $boolean . ' ') . $condition;
        }

        return [$sql, $this->bindings];
    }

    /** Bungkus nama kolom/tabel sesuai driver. */
    protected function wrap(string $value): string
    {
        if ($value === '*' || str_contains($value, '(')) {
            return $value;
        }

        $quote = Connection::driver() === 'mysql' ? '`' : '"';

        return implode('.', array_map(
            fn (string $part): string => $part === '*' ? '*' : $quote . str_replace($quote, '', $part) . $quote,
            explode('.', $value)
        ));
    }

    /** Ubah baris mentah menjadi instance model bila builder terikat model. */
    protected function hydrate(array $rows): array
    {
        if ($this->model === null) {
            return $rows;
        }

        return array_map(fn (array $row) => ($this->model)::newFromRow($row), $rows);
    }

    /*
    |----------------------------------------------------------------------
    | Eager loading
    |----------------------------------------------------------------------
    */

    /**
     * Muat semua relasi yang terdaftar di with() ke sekumpulan model.
     * Dipakai juga oleh $model->load('relasi').
     *
     * @param  Model[]  $models
     */
    public function eagerLoadOn(array $models): void
    {
        foreach ($this->eagerLoad as $name => $options) {
            $this->loadRelation($models, $name, $options);
        }
    }

    /** @param Model[] $models */
    protected function loadRelation(array $models, string $name, array $options): void
    {
        $parent = $models[0];

        if (! method_exists($parent, $name)) {
            throw new \RuntimeException(
                'Relasi [' . $name . '] tidak ditemukan di model ' . get_class($parent) . '.'
            );
        }

        $relation = $parent->{$name}();

        if (! $relation instanceof Relation) {
            throw new \RuntimeException(
                'Method [' . $name . '] pada ' . get_class($parent)
                . ' bukan relasi. Kembalikan hasMany(), hasOne(), atau belongsTo().'
            );
        }

        // Query bersih untuk model tujuan â€” bukan query milik $parent.
        $query = ($relation->relatedClass)::query();

        if ($options['nested'] !== []) {
            $query->with($options['nested']);
        }

        if ($options['constraint'] instanceof \Closure) {
            ($options['constraint'])($query);
        }

        if ($relation->type === 'belongsTo') {
            $this->matchBelongsTo($models, $query, $relation, $name);

            return;
        }

        $this->matchHasMany($models, $query, $relation, $name);
    }

    /** hasMany & hasOne: cari anak berdasarkan kunci induk. */
    protected function matchHasMany(array $models, QueryBuilder $query, Relation $relation, string $name): void
    {
        $keys = $this->collectKeys($models, $relation->localKey);

        if ($keys === []) {
            foreach ($models as $model) {
                $model->setRelation($name, $relation->relationMany ? [] : null);
            }

            return;
        }

        // Satu query untuk seluruh induk.
        $children = $query->whereIn($relation->foreignKey, $keys)->get();

        $dictionary = [];
        foreach ($children as $child) {
            $dictionary[(string) ($child->getAttributes()[$relation->foreignKey] ?? '')][] = $child;
        }

        foreach ($models as $model) {
            $key   = (string) ($model->getAttributes()[$relation->localKey] ?? '');
            $group = $dictionary[$key] ?? [];

            $model->setRelation($name, $relation->relationMany ? $group : ($group[0] ?? null));
        }
    }

    /** belongsTo: cari induk berdasarkan foreign key milik anak. */
    protected function matchBelongsTo(array $models, QueryBuilder $query, Relation $relation, string $name): void
    {
        $keys = $this->collectKeys($models, $relation->foreignKey);

        if ($keys === []) {
            foreach ($models as $model) {
                $model->setRelation($name, null);
            }

            return;
        }

        $owners = $query->whereIn($relation->localKey, $keys)->get();

        $dictionary = [];
        foreach ($owners as $owner) {
            $dictionary[(string) ($owner->getAttributes()[$relation->localKey] ?? '')] = $owner;
        }

        foreach ($models as $model) {
            $key = (string) ($model->getAttributes()[$relation->foreignKey] ?? '');

            $model->setRelation($name, $dictionary[$key] ?? null);
        }
    }

    /** Kumpulkan nilai kunci yang unik dan tidak kosong. */
    protected function collectKeys(array $models, string $key): array
    {
        $keys = [];

        foreach ($models as $model) {
            $value = $model->getAttributes()[$key] ?? null;

            if ($value !== null && $value !== '') {
                $keys[(string) $value] = $value;
            }
        }

        return array_values($keys);
    }
}

