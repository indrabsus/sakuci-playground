<?php

namespace Sakuci\Exceptions;

use Sakuci\MessageBag;

/**
 * Dilempar oleh $request->validate() saat validasi gagal.
 * Kernel akan menangkapnya dan mengembalikan user ke halaman sebelumnya.
 */
class ValidationException extends \Exception
{
    public function __construct(protected MessageBag $errors)
    {
        parent::__construct($errors->first() ?: 'Data yang diberikan tidak valid.', 422);
    }

    public function errors(): MessageBag
    {
        return $this->errors;
    }
}

