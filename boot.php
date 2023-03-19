<?php

declare(strict_types=1);

defined('ENVIRONMENT') || define('ENVIRONMENT', $_SERVER['env'] ?? 'development');
define('BASEPATH', realpath(__DIR__));

require_once BASEPATH . '/vendor/autoload.php';