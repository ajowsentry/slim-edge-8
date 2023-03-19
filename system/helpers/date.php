<?php

declare(strict_types=1);

if(! function_exists('month_add')) {

    /**
     * @param ?string $date
     * @param int $value
     * @param string $format
     * 
     * @return string
     */
    function month_add(?string $date = null, int $value = 0, string $format = 'Y-m-d'): string
    {
        $date = is_null($date) ? date('Y-m-d') : date($date);
        $dateObject = new DateTime($date);

        if($value > 0)
            return date_add($dateObject, new DateInterval("P{$value}M"))->format($format);

        elseif($value < 0)
            return date_sub($dateObject, new DateInterval("P{$value}M"))->format($format);

        else
            return $dateObject->format($format);
    }
}

if(! function_exists('next_month')) {

    /**
     * @param ?string $date
     * @param int $value
     * @param string $format
     * 
     * @return string
     */
    function next_month(?string $date = null, int $value = 1, string $format = 'Y-m-d'): string
    {
        return month_add($date, $value, $format);
    }
}

if(! function_exists('previous_month')) {

    /**
     * @param ?string $date
     * @param int $value
     * @param string $format
     * 
     * @return string
     */
    function previous_month(?string $date = null, int $value = 1, string $format = 'Y-m-d'): string
    {
        return month_add($date, -$value, $format);
    }
}

if(! function_exists('period_add')) {

    /**
     * @param ?string $period
     * @param int $value
     * 
     * @return string
     */
    function period_add(?string $period, int $value = 0): string
    {
        $date = is_null($period) ? date_create() : date_create_from_format('Ym', $period);
        if($value > 0) {
            return date_add($date, new DateInterval("P{$value}M"))->format('Ym');
        }
        elseif($value < 0) {
            $value = -$value;
            return date_sub($date, new DateInterval("P{$value}M"))->format('Ym');
        }
        else {
            return $date->format('Ym');
        }
    }
}

if(! function_exists('previous_period')) {

    /**
     * @param ?string $period
     * @param int $value
     * 
     * @return string
     */
    function previous_period(?string $period = null, int $value = 1): string
    {
        return period_add($period, -$value);
    }
}

if (!function_exists('next_period')) {
    
    /**
     * @param ?string $period
     * @param int $value
     * 
     * @return string
     */
    function next_period(?string $period = null, int $value = 1): string
    {
        return period_add($period, $value);
    }
}