<?php
declare(strict_types=1);

namespace App\Controllers\Storage\OpenFile;

use DateTime;
use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\FetchArgs;
use SlimEdge\DataTransferObject\Attributes\FetchQuery;

class DTO extends AbstractDTO
{
    #[FetchArgs]
    public string $path = '';
}