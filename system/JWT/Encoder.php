<?php

declare(strict_types=1);

namespace SlimEdge\JWT;

use Ulid\Ulid;
use InvalidArgumentException;
use Firebase\JWT\JWT as FirebaseJWT;

class Encoder
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
     * @param mixed $payload
     * @return string
     */
    public function encode(mixed $payload = null): string
    {
        $append = ['iat' => time()];

        if($this->config->hasIssuer()) {
            $append['iss'] = $this->config->getIssuer();
        }

        if($this->config->hasSubject()) {
            $append['sub'] = $this->config->getSubject();
        }

        if($this->config->hasAudience()) {
            $append['aud'] = $this->config->getAudience();
        }

        if($this->config->hasDuration()) {
            $append['exp'] = time() + $this->config->getDuration();
        }

        if($this->config->hasDelay()) {
            if(array_key_exists('exp', $append))
                $append['exp'] += $this->config->getDelay();
            
            $append['nbf'] = time() + $this->config->getDelay();
        }

        $resolvedPayload = $this->resolvePayload($payload);

        $data =  array_merge(
            ['jti' => $this->generateID()],
            $resolvedPayload,
            $append
        );

        return FirebaseJWT::encode(
            $data,
            $this->config->getPrivateKey(),
            $this->config->getAlgorithm(),
        );
    }

    /**
     * @return string
     */
    protected function generateID(): string
    {
        $ulid = strval(Ulid::generate(true));

        // 6-4-4-4-8 => xxxxxx-xxxx-xxxx-xxxx-xxxxxxxx
        return sprintf("%s-%s-%s-%s-%s",
            substr($ulid, 0, 6),
            substr($ulid, 6, 4),
            substr($ulid, 10, 4),
            substr($ulid, 14, 4),
            substr($ulid, 18, 8),
        );
    }

    /**
     * @param mixed $payload
     * @return array<string,mixed>
     */
    private function resolvePayload(mixed $payload): array
    {
        if(is_array($payload)) {
            return $payload;
        }
        elseif(is_object($payload)) {
            $resolved = [];
            foreach($payload as $prop => $val) $resolved[$prop] = $val;
            return $resolved;
        }
        elseif(is_null($payload)) {
            return [];
        }
        
        $type = typeof($payload);
        throw new InvalidArgumentException("Could not resolve 'payload' from argument type '{$type}'");
    }
}