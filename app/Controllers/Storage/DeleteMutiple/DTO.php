<?php
declare(strict_types=1);

namespace App\Controllers\Storage\DeleteMutiple;

use DateTime;
use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\FetchBody;

class DTO extends AbstractDTO
{
    #[FetchBody]
    public array $paths = [];
}