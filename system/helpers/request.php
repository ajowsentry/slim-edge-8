<?php

declare(strict_types=1);

if(! function_exists('is_ajax')) {

    /**
     * @return bool
     */
    function is_ajax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }
}

if(! function_exists('is_cli')) {

    /**
     * @return bool
     */
    function is_cli(): bool
    {
        return defined('STDIN') && PHP_SAPI === 'cli';
    }
}

if(! function_exists('get_real_ip_address')) {

    /**
     * @return string
     */
    function get_ip_address(): string
    {
        if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
            $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? '';
            $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"] ?? '';
        }
        
        $client  = $_SERVER['HTTP_CLIENT_IP'] ?? '';
        $forward = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        $remote  = $_SERVER['REMOTE_ADDR'] ?? '';

        if(filter_var($client, FILTER_VALIDATE_IP)) {
            return $client;
        }

        elseif(filter_var($forward, FILTER_VALIDATE_IP)) {
            return $forward;
        }

        else {
            return $remote;
        }
    }
}


if(! function_exists('is_https')) {

    /**
     * @return bool
     */
    function is_https(): bool
    {
        if (array_key_exists('HTTPS', $_SERVER) && $_SERVER['HTTPS'] === 'on') {
            return true;
        }

        if (array_key_exists("SERVER_PORT", $_SERVER) && 443 === (int) $_SERVER["SERVER_PORT"]) {
            return true;
        }

        if (array_key_exists("HTTP_X_FORWARDED_SSL", $_SERVER) && 'on' === $_SERVER["HTTP_X_FORWARDED_SSL"]) {
            return true;
        }

        if (array_key_exists("HTTP_X_FORWARDED_PROTO", $_SERVER) && 'https' === $_SERVER["HTTP_X_FORWARDED_PROTO"]) {
            return true;
        }

        return false;
    }
}

if(! function_exists('get_base_path')) {

    /**
     * @return ?string
     */
    function get_base_path(): ?string
    {
        if(!is_cli()) {
            $index = 0;
            $scriptName = $_SERVER['SCRIPT_NAME'];
            $requestUri = $_SERVER['REQUEST_URI'];
            while(strpos($requestUri, '//') !== false) {
                $requestUri = str_replace('//', '/', $requestUri);
            }

            for ($i = 0; $i < min(strlen($scriptName), strlen($requestUri)); $i++) {
                if($requestUri[$i] == $scriptName[$i]) {
                    if($requestUri[$i] == '/')
                        $index = $i;
                }
                else break;
            }

            return '/' . trim(substr($scriptName, 0, $index), '/');
        }

        return null;
    }
}

if(! function_exists('get_base_url')) {

    /**
     * @return string
     */
    function get_base_url(): string
    {
        static $baseURL = null;
        if (is_null($baseURL)) {
            $baseURL = is_https() ? 'https' : 'http';
            $baseURL .= '://' . ($_SERVER['SERVER_NAME'] ?? '???');
            $baseURL .= get_base_path() . '/';
        }

        return $baseURL;
    }
}

if(! function_exists('url')) {
    
    /**
     * @param string $path
     * @return string
     */
    function url(string $path = ''): string
    {
        $url = get_base_url() . '/' . $path;
        while (strpos($url, '//') !== false) {
            $url = str_replace('//', '/', $url);
        }
        return $url;
    }
}