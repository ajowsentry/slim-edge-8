<?php

declare(strict_types=1);

namespace App\DTO;

use Laminas\Diactoros\UploadedFile;
use SlimEdge\DataTransferObject\AbstractDTO;
use SlimEdge\DataTransferObject\Attributes\FetchFile;

class UploadFileDTO extends AbstractDTO
{
    #[FetchFile]
    public UploadedFile $file;
}