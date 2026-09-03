<?php

namespace App\Models;

use Sakuci\Database\Model;
use Sakuci\Session;

class User extends Model
{
    protected static ?string $table = 'users';

    // Kolom yang boleh diisi lewat create()/update()
    protected array $fillable = ['username', 'password', 'role'];

    // Password tidak boleh tampil saat di-convert ke array/JSON
    protected array $hidden = ['password'];

    /** Cek apakah user punya salah satu role yang diberikan. */
    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    /**
     * User yang sedang login pada request ini (null bila belum login).
     * Hasil di-cache supaya query hanya jalan sekali per request.
     */
    public static function current(): ?static
    {
        static $user = null;
        static $resolved = false;

        if (! $resolved) {
            $resolved = true;

            if (Session::has('user_id')) {
                $user = static::find(Session::get('user_id'));
            }
        }

        return $user;
    }
}

