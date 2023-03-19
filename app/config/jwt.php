<?php

declare(strict_types=1);

use SlimEdge\JWT\KeySource;

return [
    'algorithm' => 'RS256',

    'issuer' => get_base_url(),

    'duration' => '3 hours',

    'privateKey' => KeySource::create(BASEPATH . '/resource/keys/jwt.key'),
    'publicKey' => KeySource::create(BASEPATH . '/resource/keys/jwt.key.pub'),
];