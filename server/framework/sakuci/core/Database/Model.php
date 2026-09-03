<?php

namespace Sakuci\Database;

use Sakuci\Exceptions\HttpException;

/**
 * Model bergaya Eloquent (versi ringan).
 *
 *   class Post extends Model
 *   {
 *       protected static ?string $table = 'posts';
 *       protected array $fillable = ['title', 'body'];
 *   }
 *
 *   Post::all();
 *   Post::where('status', 'draft')->get();
 *   Post::create(['title' => 'Halo']);
 *
 * @method static QueryBuilder with(string|array $relations, ?\Closure $constraint = null)
 * @method static QueryBuilder where(string|array|\Closure $column, mixed $operator = null, mixed $value = null)
 * @method static QueryBuilder orWhere(string $column, mixed $operator = null, mixed $value = null)
 * @method static QueryBuilder whereIn(string $column, array $values)
 * @method static QueryBuilder whereNull(string $column)
 * @method static QueryBuilder orderBy(string $column, string $direction = 'asc')
 * @method static QueryBuilder latest(string $column = 'id')
 * @method static QueryBuilder limit(int $limit)
 * @method static QueryBuilder select(string|array $columns)
 * @method static Paginator    paginate(int $perPage = 15)
 * @method static int          count(string $column = '*')
 * @method static static|null  first()
 */
abstract class Model implements \JsonSerializable
{
    /** Nama tabel; jika null ditebak dari nama class (Post -> posts). */
    protected static ?string $table = null;

    protected string $primaryKey = 'id';

    /** Kolom yang boleh diisi massal lewat create()/fill(). */
    protected array $fillable = [];

    /** Kolom yang disembunyikan saat toArray()/JSON. */
    protected array $hidden = [];

    /** Cast otomatis: ['aktif' => 'bool', 'meta' => 'array', 'harga' => 'float'] */
    protected array $casts = [];

    /** Isi created_at & updated_at secara otomatis. */
    public bool $timestamps = true;

    protected array $attributes = [];

    protected array $original = [];

    /** Cache hasil relasi yang sudah dimuat. */
    protected array $relations = [];

    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    /*
    |----------------------------------------------------------------------
    | Atribut
    |----------------------------------------------------------------------
    */

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->attributes[$key] = $value;
            }
        }

        return $this;
    }

    /** Isi atribut tanpa memperhatikan $fillable. */
    public function forceFill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->attributes[$key] = $value;
        }

        return $this;
    }

    protected function isFillable(string $key): bool
    {
        if ($this->fillable === []) {
            return $key !== $this->primaryKey;
        }

        return in_array($key, $this->fillable, true);
    }

    public function __get(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castValue($key, $this->attributes[$key]);
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        // Relasi didefinisikan sebagai method: $post->comments
        if (method_exists($this, $key)) {
            $relation = $this->{$key}();

            if ($relation instanceof QueryBuilder) {
                return $this->relations[$key] = $relation->relationMany
                    ? $relation->get()
                    : $relation->first();
            }

            return $this->relations[$key] = $relation;
        }

        return null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]) || isset($this->relations[$key]) || method_exists($this, $key);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    protected function castValue(string $key, mixed $value): mixed
    {
        if ($value === null || ! isset($this->casts[$key])) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'bool', 'boolean' => (bool) $value,
            'string'          => (string) $value,
            'array', 'json'   => is_array($value) ? $value : (json_decode((string) $value, true) ?? []),
            default           => $value,
        };
    }

    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /*
    |----------------------------------------------------------------------
    | Query
    |----------------------------------------------------------------------
    */

    public static function table(): string
    {
        if (static::$table !== null) {
            return static::$table;
        }

        $class = substr(strrchr(static::class, '\\') ?: static::class, 1) ?: static::class;

        return str_plural(str_snake($class));
    }

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::table(), static::class);
    }

    /** @return static[] */
    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(mixed $id): ?static
    {
        return static::query()->find($id, (new static())->getKeyName());
    }

    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new HttpException(404, 'Data dengan ID ' . $id . ' tidak ditemukan.');
        }

        return $model;
    }

    public static function firstWhere(string $column, mixed $value): ?static
    {
        return static::query()->where($column, '=', $value)->first();
    }

    public static function create(array $attributes): static
    {
        $model = new static($attributes);
        $model->save();

        return $model;
    }

    public static function destroy(mixed $id): bool
    {
        $model = static::find($id);

        return $model !== null && $model->delete();
    }

    /** Bangun instance dari baris database (sudah dianggap tersimpan). */
    public static function newFromRow(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->original   = $row;
        $model->exists     = true;

        return $model;
    }

    /** Method statis yang tidak dikenal diteruskan ke query builder. */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        return static::query()->{$method}(...$arguments);
    }

    public function __call(string $method, array $arguments): mixed
    {
        return static::query()->{$method}(...$arguments);
    }

    /*
    |----------------------------------------------------------------------
    | Simpan / hapus
    |----------------------------------------------------------------------
    */

    public function save(): bool
    {
        $attributes = $this->attributes;

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');

            if (! $this->exists) {
                $attributes['created_at'] = $attributes['created_at'] ?? $now;
            }

            $attributes['updated_at'] = $now;
        }

        // Nilai array/bool disiapkan agar aman disimpan.
        foreach ($attributes as $key => $value) {
            if (is_array($value)) {
                $attributes[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } elseif (is_bool($value)) {
                $attributes[$key] = (int) $value;
            }
        }

        if ($this->exists) {
            $dirty = array_diff_assoc(
                array_map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $attributes),
                array_map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $this->original)
            );

            unset($dirty[$this->primaryKey]);

            if ($dirty === []) {
                return true;
            }

            static::query()->where($this->primaryKey, '=', $this->getKey())->update($dirty);
        } else {
            unset($attributes[$this->primaryKey]);

            $id = static::query()->insert($attributes);

            $attributes[$this->primaryKey] = is_numeric($id) ? (int) $id : $id;
            $this->exists = true;
        }

        $this->attributes = $attributes;
        $this->original   = $attributes;

        return true;
    }

    public function update(array $attributes): bool
    {
        return $this->fill($attributes)->save();
    }

    public function delete(): bool
    {
        if (! $this->exists) {
            return false;
        }

        static::query()->where($this->primaryKey, '=', $this->getKey())->delete();
        $this->exists = false;

        return true;
    }

    public function refresh(): static
    {
        $fresh = static::find($this->getKey());

        if ($fresh !== null) {
            $this->attributes = $fresh->getAttributes();
            $this->original   = $this->attributes;
            $this->relations  = [];
        }

        return $this;
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    /*
    |----------------------------------------------------------------------
    | Relasi
    |----------------------------------------------------------------------
    */

    /** Satu induk memiliki banyak anak: $user->posts */
    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): Relation
    {
        $foreignKey ??= str_snake(class_basename(static::class)) . '_id';
        $localKey   ??= $this->primaryKey;

        $relation = new Relation('hasMany', $related, $foreignKey, $localKey);
        $relation->where($foreignKey, '=', $this->attributes[$localKey] ?? null);

        return $relation;
    }

    /** Satu induk memiliki satu anak. */
    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): Relation
    {
        $foreignKey ??= str_snake(class_basename(static::class)) . '_id';
        $localKey   ??= $this->primaryKey;

        $relation = new Relation('hasOne', $related, $foreignKey, $localKey);
        $relation->where($foreignKey, '=', $this->attributes[$localKey] ?? null);

        return $relation;
    }

    /** Anak menunjuk ke induk: $post->user */
    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): Relation
    {
        $foreignKey ??= str_snake(class_basename($related)) . '_id';
        $ownerKey   ??= 'id';

        $relation = new Relation('belongsTo', $related, $foreignKey, $ownerKey);
        $relation->where($ownerKey, '=', $this->attributes[$foreignKey] ?? null);

        return $relation;
    }

    /*
    |----------------------------------------------------------------------
    | Pengelolaan relasi yang sudah dimuat
    |----------------------------------------------------------------------
    */

    /** Diisi oleh eager loading; membuat $model->relasi tidak query lagi. */
    public function setRelation(string $name, mixed $value): static
    {
        $this->relations[$name] = $value;

        return $this;
    }

    public function relationLoaded(string $name): bool
    {
        return array_key_exists($name, $this->relations);
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function unsetRelation(string $name): static
    {
        unset($this->relations[$name]);

        return $this;
    }

    /**
     * Eager loading setelah model terlanjur diambil.
     *
     *   $post = Post::find(1);
     *   $post->load('comments.user');
     */
    public function load(string|array $relations): static
    {
        static::query()->with($relations)->eagerLoadOn([$this]);

        return $this;
    }

    /** Sama seperti load(), tetapi melewati relasi yang sudah dimuat. */
    public function loadMissing(string|array $relations): static
    {
        $pending = [];

        foreach ((array) $relations as $key => $value) {
            $name = is_int($key) ? $value : $key;

            if (is_string($name) && ! $this->relationLoaded(explode('.', $name)[0])) {
                $pending[$key] = $value;
            }
        }

        return $pending === [] ? $this : $this->load($pending);
    }

    /*
    |----------------------------------------------------------------------
    | Serialisasi
    |----------------------------------------------------------------------
    */

    public function toArray(): array
    {
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if (! in_array($key, $this->hidden, true)) {
                $data[$key] = $this->castValue($key, $value);
            }
        }

        foreach ($this->relations as $key => $relation) {
            if (is_array($relation)) {
                $data[$key] = array_map(fn ($item) => $item instanceof self ? $item->toArray() : $item, $relation);
            } elseif ($relation instanceof self) {
                $data[$key] = $relation->toArray();
            }
        }

        return $data;
    }

    public function toJson(int $flags = JSON_UNESCAPED_UNICODE): string
    {
        return (string) json_encode($this->toArray(), $flags);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}

