<?php

declare(strict_types=1);

define('BASEPATH', realpath(__DIR__));

require_once BASEPATH . '/vendor/autoload.php';

SlimEdge\Kernel::boot();