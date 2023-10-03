<?php
declare(strict_types=1);

namespace App\Controllers\LogReader;

use DateTime;
use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\FetchQuery;

class DTO extends AbstractDTO
{
    #[FetchQuery]
    public DateTime $date;

    #[FetchQuery]
    public int $limit = 100;

    #[FetchQuery]
    public int $offset = 0;

    protected function getDefaultDate()
    {
        return new DateTime;
    }
}