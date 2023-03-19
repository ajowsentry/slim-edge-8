<?php

declare(strict_types=1);

return [
    'default' => 'main',
    'connections' => [
        'main' => [
            'driver'    => 'mysql',
            'host'      => 'localhost',
            'port'      => '3306',
            'database'  => 'db_example',
            'username'  => 'root',
            'password'  => '',
            /**
             * Possible extra config for sqsrv driver:
             * - charset
             * {@see https://www.php.net/manual/en/ref.pdo-sqlsrv.connection.php}
             */
        ],
        'postgres' => [
            'driver'    => 'pgsql',
            'host'      => 'localhost',
            'port'      => '3306',
            'database'  => 'db_example',
            'username'  => 'root',
            'password'  => '',
            /**
             * Possible extra config for pgsql driver:
             * - sslmode
             * {@see https://www.php.net/manual/en/ref.pdo-pgsql.connection.php}
             */
        ],
        'sqlServer' => [
            'driver'    => 'sqlsrv',
            'host'      => 'localhost',
            'database'  => 'db_example',
            'username'  => 'root',
            'password'  => '',
            /**
             * Possible extra config for sqlsrv driver:
             * - connectionPooling
             * - encrypt
             * - failoverPartner
             * - loginTimeout
             * - multipleActiveResultSets
             * - quotedID
             * - traceFile
             * - traceOn
             * - transactionIsolation
             * - trustServerCertificate
             * {@see https://www.php.net/manual/en/ref.pdo-sqlsrv.connection.php}
             */
        ],
        'dblib' => [
            'driver'    => 'dblib',
            'host'      => 'localhost',
            'database'  => 'db_example',
            'username'  => 'root',
            'password'  => '',
            /**
             * Possible extra config for dblib driver:
             * - charset
             * - appname
             * {@see https://www.php.net/manual/en/ref.pdo-dblib.connection.php}
             */
        ],
        'oracle' => [
            'driver'    => 'oci',
            'host'      => 'localhost',
            'database'  => 'db_example',
            'username'  => 'root',
            'password'  => '',
            /**
             * Possible extra config for oci driver:
             * - charset
             * {@see https://www.php.net/manual/en/ref.pdo-oci.connection.php}
             */
        ],

    ],
];
