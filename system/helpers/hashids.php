<?php

declare(strict_types=1);

use Hashids\Hashids;

if(! function_exists('hashids'))
{
    /**
     * @param ?string $key
     * @return Hashids
     */
    function hashids(?string $key = null)
    {
        return container($key ? "hashids.{$key}" : Hashids::class);
    }
}

if(! function_exists('hashids_encode'))
{
    /**
     * @param int ...$numbers
     * @return string Encoded hashids
     */
    function hashids_encode(int ...$numbers): string
    {
        /** @var Hashids $hashids */
        $hashids = container(Hashids::class);

        return $hashids->encode(...$numbers);
    }
}

if(! function_exists('hashids_decode'))
{
    /**
     * @param string $hash
     * @return int[] Decoded hashids
     */
    function hashids_decode(string $hash): array
    {
        /** @var Hashids $hashids */
        $hashids = container(Hashids::class);

        return $hashids->decode($hash);
    }
}