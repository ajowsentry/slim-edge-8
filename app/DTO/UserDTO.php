<?php

declare(strict_types=1);

namespace App\DTO;

use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\ExposeJson;
use SlimEdge\DataTransferObject\Attributes\FetchBody;
use SlimEdge\DataTransferObject\Attributes\TypeOf;

class UserDTO extends AbstractDTO
{
    #[FetchBody]
    public string $username;

    #[FetchBody]
    public PasswordField $password;

    #[FetchBody]
    public ?StatusEnum $state = null;

    #[FetchBody]
    #[ExposeJson(false)]
    #[TypeOf('int', isCollection: true)]
    public array $roles = [];
}