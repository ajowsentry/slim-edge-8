<?php

declare(strict_types=1);

use Symfony\Component\Mime\MimeTypes;

if(! function_exists('get_mime_types'))
{
    /**
     * Gets the MIME types for the given extension in decreasing
     * order of preference.
     * 
     * @param string $extension
     * @return string[]
     */
    function get_mime_types(string $extension): array
    {
        return MimeTypes::getDefault()->getMimeTypes($extension);
    }
}

if(! function_exists('get_extensions'))
{
    /**
     * Gets the extensions for the given MIME type in decreasing order
     * of preference.
     * 
     * @param string $mimeType
     * @return string[]
     */
    function get_extensions(string $mimeType): array
    {
        return MimeTypes::getDefault()->getExtensions($mimeType);
    }
}

if(! function_exists('guess_mime_type'))
{
    /**
     * The file is passed to each registered MIME type guesser in reverse
     * order of their registration (last registered is queried first).
     * Once a guesser returns a value that is not null, this method
     * terminates and returns the value.
     * 
     * @param string $path
     * @return ?string
     */
    function guess_mime_type(string $path): ?string
    {
        return MimeTypes::getDefault()->guessMimeType($path);
    }
}