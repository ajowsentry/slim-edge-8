<?php

declare(strict_types=1);

if(! function_exists('clamp')) {

    /**
     * @template T of int|float
     * @param T $number
     * @param int|float $min
     * @param int|float $max
     * @return T
     */
    function clamp(int|float $number, int|float $min = -INF, int|float $max = INF): int|float
    {
        if($number < $min)
            $result = $min;

        elseif($number > $max)
            $result = $max;

        else $result = $number;

        return is_int($number) ? intval($result) : floatval($result);
    }
}