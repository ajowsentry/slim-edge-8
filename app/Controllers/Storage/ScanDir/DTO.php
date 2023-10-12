<?php
declare(strict_types=1);

namespace App\Controllers\Storage\ScanDir;

use DateTime;
use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\FetchArgs;
use SlimEdge\DataTransferObject\Attributes\FetchQuery;

class DTO extends AbstractDTO
{
    #[FetchQuery]
    public int $limit = 100;

    #[FetchQuery]
    public int $offset = 0;

    #[FetchQuery]
    public string $select = '';

    #[FetchArgs]
    public string $path = '';

    public function hasSelect($option): bool
    {
        return stripos(',' . $this->select . ',', ',' . $option . ',') !== false;
    }
}