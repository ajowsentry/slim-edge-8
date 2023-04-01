<?php

declare(strict_types=1);

namespace App\DTO;

use Respect\Validation\Rules;
use Respect\Validation\Validatable;
use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\ExposeJson;
use SlimEdge\DataTransferObject\Attributes\FetchBody;
use SlimEdge\DataTransferObject\Attributes\TypeOf;

class GenericDTO extends AbstractDTO
{
    #[FetchBody('setring')]
    public string $string;

    #[FetchBody]
    public int $integer;

    #[FetchBody]
    public float $double;

    #[FetchBody]
    public bool $boolean;

    #[FetchBody]
    public mixed $mixed;

    #[FetchBody]
    public UserDTO $user;

    #[FetchBody]
    public GenericDTO $generic;

    public static function getStringValidator(): Validatable
    {
        return new Rules\Length(8, 8);
    }

    public static function getMixedValidator(): Validatable
    {
        return new Rules\Alnum();
    }
}