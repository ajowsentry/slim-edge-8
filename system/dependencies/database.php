<?php

declare(strict_types=1);

use DI\Factory\RequestedEntry;
use SqlTark\XQuery;
use SqlTark\Manager\DbManager;

$dependencies = [
    DbManager::class => DI\factory(function(?array $config): DbManager {
        return new DbManager($config);
    })->parameter('config', DI\get('config.database')),

    'db' => DI\factory(function(DbManager $db): XQuery {
        return $db->connection();
    }),
];

$config = load_config('database');
if(isset($config['connections'])) {
    foreach(array_keys($config['connections']) as $key) {
        $dependencies["db.{$key}"] = DI\factory(function(RequestedEntry $entry, DbManager $db): XQuery {
            [, $key] = explode('.', $entry->getName(), 2);
            return $db->connection($key);
        });
    }
}

return $dependencies;