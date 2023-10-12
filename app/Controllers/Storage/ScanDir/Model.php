<?php
declare(strict_types=1);

namespace App\Controllers\Storage\ScanDir;

use SlimEdge\Support\Paths;

class Model
{
    /**
     * @param DTO $formData
     */
    public function scanDirectory($formData)
    {
        $path = realpath(Paths::Storage . '/' . ltrim($formData->path, '/'));
        
        if(false === $path || !is_dir($path))
            return 0;

        $count = 0;
        $result = [];
        try {
            $handle = opendir($path);
            while(false !== ($file = readdir($handle))) {
                if($file === '.' || $file === '..') {
                    continue;
                }

                $count++;
                if($count > $formData->offset && count($result) <= $formData->limit) {
                    $filePath = $path . DIRECTORY_SEPARATOR . $file;

                    $fileInfo = new \stdClass();
                    $fileInfo->filename = $file;
                    $fileInfo->path = str_replace('\\', '/', substr($filePath, strlen(Paths::Storage)));
                    $fileInfo->type = filetype($filePath);

                    if($formData->hasSelect('permission')) {
                        $fileInfo->permission = fileperms($filePath);
                    }

                    if($formData->hasSelect('last_access')) {
                        $fileInfo->last_access = fileatime($filePath);
                    }

                    if($formData->hasSelect('last_modified')) {
                        $fileInfo->last_modified = filemtime($filePath);
                    }

                    if($formData->hasSelect('created_at')) {
                        $fileInfo->created_at = filectime($filePath);
                    }

                    if($fileInfo->type == 'file' && $formData->hasSelect('content_type')) {
                        $fileInfo->content_type = mime_content_type($filePath);
                    }

                    if($fileInfo->type == 'file' && $formData->hasSelect('size')) {
                        $fileInfo->size = filesize($filePath);
                    }

                    if($fileInfo->type == 'dir') {
                        $fileInfo->url = url_for('storageScanDir', [
                            'path' => ltrim($fileInfo->path, '/')
                        ]);
                    }
                    elseif($fileInfo->type == 'file') {
                        $fileInfo->url = url_for('storageOpenFile', [
                            'path' => ltrim($fileInfo->path, '/')
                        ]);
                    }

                    array_push($result, $fileInfo);
                }
            }
        }
        finally {
            if(isset($handle) && $handle !== false)
                closedir($handle);
        }

        return [
            'count'  => $count,
            'limit'  => $formData->limit,
            'offset' => $formData->offset,
            'data'   => $result,
        ];
    }
}