<?php

namespace App\Models;

use Sakuci\Database\Model;

class Mahasiswa extends Model
{
    protected static ?string $table = 'mahasiswa';

    protected string $primaryKey = 'id';

    public bool $timestamps = false;

    protected array $fillable = ['nim', 'nama', 'jurusan', 'email', 'ipk'];
}
