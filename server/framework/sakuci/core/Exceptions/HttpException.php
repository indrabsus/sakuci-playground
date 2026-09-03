<?php

namespace Sakuci\Exceptions;

/**
 * Exception yang membawa status code HTTP, dipakai oleh helper abort().
 */
class HttpException extends \Exception
{
    protected static array $defaultMessages = [
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Halaman tidak ditemukan',
        405 => 'Method Not Allowed',
        419 => 'Halaman kedaluwarsa (CSRF token tidak valid)',
        422 => 'Data tidak valid',
        500 => 'Terjadi kesalahan pada server',
        503 => 'Layanan sedang tidak tersedia',
    ];

    public function __construct(protected int $statusCode = 404, string $message = '')
    {
        parent::__construct($message ?: (static::$defaultMessages[$statusCode] ?? 'Error'), $statusCode);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

