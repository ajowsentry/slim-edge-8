<?php

declare(strict_types=1);

define('ENVIRONMENT', 'development');

require_once '../boot.php';

SlimEdge\Kernel::boot()->getApp()->run();