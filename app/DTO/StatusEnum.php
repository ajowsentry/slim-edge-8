<?php

declare(strict_types=1);

namespace App\DTO;

enum StatusEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}