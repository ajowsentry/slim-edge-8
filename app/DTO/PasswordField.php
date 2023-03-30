<?php

namespace App\DTO;

use SlimEdge\DataTransferObject\ScalarDTO;

class PasswordField extends ScalarDTO
{
    protected string $type = 'string';

    public function getHash(): string
    {
        return password_hash($this->value, PASSWORD_BCRYPT);
    }

    public function verify(string $hash): bool
    {
        return password_verify($this->value, $hash);
    }
}