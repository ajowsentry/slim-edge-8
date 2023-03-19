<?php

declare(strict_types=1);

namespace SlimEdge\JWT;

use Firebase\JWT\Key;
use InvalidArgumentException;
use UnexpectedValueException;
use Firebase\JWT\JWT as FirebaseJWT;

class Decoder
{
    /**
     * @var Config $config
     */
    private Config $config;

    /**
     * @param array<string,mixed>|Config $config
     */
    public function __construct(array|Config $config)
    {
        $this->config = $config instanceof Config ? $config : new Config($config);
    }

    /**
     * @param string $token
     * @return object
     */
    public function decode(string $token): object
    {
        $key = new Key($this->config->getPublicKey(), $this->config->getAlgorithm());
        return FirebaseJWT::decode($token, $key);
    }

    /**
     * @param string $token
     * @return bool
     */
    public function verify(string $token): bool
    {
        try {
            $this->decode($token);
            return true;
        }
        catch(UnexpectedValueException|InvalidArgumentException) {
            return false;
        }
    }
}