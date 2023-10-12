<?php
declare(strict_types=1);

namespace App\Controllers\Storage\DirSize;

use SlimEdge\Support\Paths;

class Model
{
    /**
     * @param DTO $formData
     */
    public function dirSize($formData)
    {
        $path = realpath(Paths::Storage . '/' . ltrim($formData->path, '/'));
        
        if(false === $path || !is_dir($path))
            return false;

        $size = 0;
        foreach(dir_files($path) as $file) {
            $size += filesize($file);
        }

        return $size;
    }
}