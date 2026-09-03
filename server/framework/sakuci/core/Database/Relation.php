<?php

namespace Sakuci\Database;

/**
 * Query builder yang membawa metadata relasi.
 *
 * Metadata inilah yang membuat eager loading (with()) mungkin dilakukan:
 * tanpa mengetahui kelas terkait beserta pasangan kuncinya, kita tidak bisa
 * mengambil seluruh data anak dalam satu query.
 *
 * Karena mewarisi QueryBuilder, relasi tetap bisa dirantai seperti biasa:
 *
 *   $post->comments()->where('approved', 1)->orderBy('id')->get();
 */
class Relation extends QueryBuilder
{
    /**
     * @param  string  $type          hasMany | hasOne | belongsTo
     * @param  string  $relatedClass  kelas model tujuan
     * @param  string  $foreignKey    hasMany/hasOne: kolom di tabel anak;
     *                                belongsTo: kolom di tabel model ini
     * @param  string  $localKey      hasMany/hasOne: kolom di tabel model ini;
     *                                belongsTo: kolom di tabel induk (owner key)
     */
    public function __construct(
        public string $type,
        public string $relatedClass,
        public string $foreignKey,
        public string $localKey
    ) {
        parent::__construct($relatedClass::table(), $relatedClass);

        $this->relationMany = $type === 'hasMany';
    }

    public function isSingle(): bool
    {
        return ! $this->relationMany;
    }
}

