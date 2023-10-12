<?php
declare(strict_types=1);

namespace App\Controllers\Storage\Create;

use DateTime;
use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\FetchArgs;
use SlimEdge\DataTransferObject\Attributes\FetchQuery;

class DTO extends AbstractDTO
{
    #[FetchArgs]
    public string $path = '';

    #[FetchQuery]
    public bool $append = false;

    #[FetchQuery]
    public bool $overwrite = false;

    #[FetchQuery]
    public int $offset = 0;

    #[FetchQuery]
    public string $type = 'file';
}