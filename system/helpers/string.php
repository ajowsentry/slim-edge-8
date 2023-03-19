<?php

declare(strict_types=1);

if(! function_exists('random_string'))
{
    /**
     * @param int $length
     * @param string $pool
     * @return string
     */
    function random_string(int $length = 10, string $pool = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm1234567890-_'): string
    {
        $str = '';
        while(strlen($str) < $length) {
            $str .= $pool[random_int(0, strlen($str))];
        }
        return $str;
    }
}

if(! function_exists('str_pad_left'))
{
    /**
     * @param string $string
     * @param int $length
     * @param string $padString
     * @return string
     */
    function str_pad_left(string $string, int $length, string $padString = ' '): string
    {
        return str_pad($string, $length, $padString, STR_PAD_LEFT);
    }
}

if(! function_exists('str_pad_right'))
{
    /**
     * @param string $string
     * @param int $length
     * @param string $padString
     * @return string
     */
    function str_pad_right(string $string, int $length, string $padString = ' '): string
    {
        return str_pad($string, $length, $padString, STR_PAD_RIGHT);
    }
}

if(! function_exists('is_binary'))
{
    /**
     * @param string $string
     * @return bool
     */
    function is_binary(string $string): bool
    {
        return preg_match('~[^\x20-\x7E\t\r\n]~', $string) > 0;
    }
}

if(! function_exists('chop_string'))
{
    /**
     * @param string $string Input string
     * @param int $length Maximum string length
     * 
     * @return string Chopped string
     */
    function chop_string(string $string, int $length): string
    {
        return strlen($string) > $length ? substr($string, 0, $length) : $string;
    }
}

if(! function_exists('to_camel_case'))
{
    /**
     * @param string $string Snake-cased string
     * @return string Camel cased string
     */
    function to_camel_case(string $string): string
    {
        return preg_replace_callback("/_+(\w)/", function($match) {
            return strtoupper($match[1][0]);
        }, $string);
    }
}

if(! function_exists('to_pascal_case'))
{
    /**
     * @param string $string Snake-cased string
     * @return string Pascal cased string
     */
    function to_pascal_case(string $string): string
    {
        return ucfirst(to_camel_case($string));
    }
}

if(! function_exists('to_snake_case'))
{
    /**
     * @param string $string Pascal or camel cased string
     * @return string Snake cased string
     */
    function to_snake_case(string $string): string
    {
        return ltrim(preg_replace_callback("/([A-Z])/", function($match) {
            return '_' . strtolower($match[1][0]);
        }, $string), '_');
    }
}

if(! function_exists('string_equals'))
{
    /**
     * Compare string with timing attack prevention
     * 
     * @param string $str1
     * @param string $str2
     * 
     * @return bool
     */
    function string_equals(string $str1, string $str2): bool
    {
        if(strlen($str1) < strlen($str2)) {
            $str1 = str_pad_right($str1, strlen($str2));
        }
        elseif(strlen($str1) > strlen($str2)) {
            $str2 = str_pad_right($str2, strlen($str1));
        }

        $equals = true;
        for($i = 0; $i < strlen($str1); $i++) {
            $equals = $equals & $str1[$i] === $str2[$i];
        }
        
        return $equals;
    }
}

if(! function_exists('urlsafe_base64_encode'))
{
    /**
     * @param string $value
     * @return string
     */
    function urlsafe_base64_encode(string $value): string
    {
        return strtr(base64_encode($value), '+/=', '._-');
    }
}

if(! function_exists('urlsafe_base64_decode'))
{
    /**
     * @param string $value
     * @return string
     */
    function urlsafe_base64_decode(string $value): string
    {
        return base64_decode(strtr($value, '._-', '+/='));
    }
}

if(! function_exists('remove_invisible_characters'))
{
    /**
     * @param string $string
     * @return string Removed invisible characters
     */
    function remove_invisible_characters(string $string): string
    {
        return preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $string);
    }
}