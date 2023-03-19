<?php

declare(strict_types=1);

if(! function_exists('array_merge_deep')) {

    /**
     * @param array<mixed,mixed> $array
     * @param array<mixed,mixed> ...$arrays
     * @return array<mixed,mixed>
     */
    function array_merge_deep(array $array, array ...$arrays): array
    {
        foreach($arrays as $array2) {
            foreach($array2 as $key => $value2) {
                if(array_key_exists($key, $array) && is_array($value2)) {
                    $value = $array[$key];
                    if(is_array($value)) {
                        $value2 = array_merge_deep($value, $value2);
                    }
                }

                $array[$key] = $value2;
            }
        }
        return $array;
    }
}