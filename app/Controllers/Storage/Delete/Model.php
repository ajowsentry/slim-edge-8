<?php
declare(strict_types=1);

namespace App\Controllers\Storage\Delete;

use SlimEdge\Support\Paths;

class Model
{
    /**
     * @param DTO $formData
     */
    public function delete($formData)
    {
        $path = realpath(Paths::Storage . '/' . ltrim($formData->path, '/'));
        
        if(false === $path)
            return false;

        if(is_file($path)) {
            $size = filesize($path);
            unlink($path);
            
            return [['path' => $formData->path, 'size' => $size]];
        }
        elseif(is_dir($path)) {
            $result = [];

            foreach(dir_files($path) as $file) {
                array_push($result, [
                    'path' => substr($file, strlen(Paths::Storage)),
                    'size' => filesize($path),
                ]);
            }

            delete_dir($path);
            return $result;
        }
    }
}