<?php
declare(strict_types=1);

namespace App\Controllers\Storage\DeleteMutiple;

use SlimEdge\Support\Paths;

class Model
{
    /**
     * @param DTO $formData
     */
    public function deleteMultiple($formData)
    {
        $result = [];
        foreach($formData->paths as $path) {
            if(is_string($path)) {
                array_push($result, $this->delete($path));
            }
        }

        return $result;
    }

    /**
     * @param string $path
     */
    public function delete($path)
    {
        $realpath = realpath(Paths::Storage . '/' . ltrim($path, '/'));
        
        if(false === $realpath || !is_file($realpath) || !str_starts_with($realpath, realpath(Paths::Storage)))
            return ['path' => $path, 'size' => 0, 'success' => false];

        $size = filesize($realpath);
        unlink($realpath);
        
        return ['path' => $path, 'size' => $size, 'success' => true];
    }
}