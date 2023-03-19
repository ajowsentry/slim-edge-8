<?php

declare(strict_types=1);

use Ulid\Ulid;

if(! function_exists('ulid_generate'))
{
    function ulid_generate(bool $lowercase = false): string
    {
        return (string) Ulid::generate($lowercase);
    }
}