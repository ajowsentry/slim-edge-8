<?php
declare(strict_types=1);

namespace App\Controllers\Storage\OpenFile;

use SlimEdge\Support\Paths;

class Model
{
    /**
     * @param DTO $formData
     */
    public function openFile($formData)
    {
        $path = realpath(Paths::Storage . '/' . ltrim($formData->path, '/'));
        
        if(false === $path || !is_file($path))
            return 0;
        
        return $path;
    }
}