<?php

use DI\Factory\RequestedEntry;
use Hashids\Hashids;

return [
    
    'hashids' => DI\get(Hashids::class),

    Hashids::class => DI\factory(function(?array $config): Hashids {
        $config = $config['_default'] ?? [];
        $salt       = $config['salt']       ?? '';
        $length     = $config['length']     ?? 0;
        $characters = $config['characters'] ?? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

        return new Hashids($salt, $length, $characters);
    })->parameter('config', DI\get('config.hashids')),

    'hashids.*' => DI\factory(function(RequestedEntry $entry, array $config): Hashids {
        [, $key] = explode('.', $entry->getName());
        $default = $config['_default'];
        
        $config = $config[$key] ?? [];
        $salt       = $config['salt']       ?? $default['salt']       ?? '';
        $length     = $config['length']     ?? $default['length']     ?? 0;
        $characters = $config['characters'] ?? $default['characters'] ?? 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';

        return new Hashids($salt, $length, $characters);
    })->parameter('config', DI\get('config.hashids')),
];