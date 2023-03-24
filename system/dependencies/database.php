<?php

declare(strict_types=1);

use SqlTark\XQuery;
use DI\Factory\RequestedEntry;
use SqlTark\Manager\DbManager;

return [
    
    DbManager::class => DI\factory(function(?array $config): DbManager {
        return new DbManager($config);
    })->parameter('config', DI\get('config.database')),

    'db' => DI\factory(function(DbManager $db): XQuery {
        return $db->connection();
    }),

    'db.*' => DI\factory(function(RequestedEntry $entry, DbManager $db): XQuery {
        [, $key] = explode('.', $entry->getName(), 2);
        return $db->connection($key);
    }),
];