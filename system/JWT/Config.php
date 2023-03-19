<?php

declare(strict_types=1);

namespace SlimEdge\JWT;

use SlimEdge\Exceptions\ConfigException;

class Config
{
    /**
     * @var string $header
     */
    private string $header = 'Authorization';

    /**
     * @var string $pattern
     */
    private string $pattern = '/Bearer\s+([\w-]+\.[\w-]+\.[\w-]+)/';
    
    /**
     * @var ?string $issuer
     */
    private ?string $issuer = null;
    
    /**
     * @var ?string $subject
     */
    private ?string $subject = null;
    
    /**
     * @var ?string $audience
     */
    private ?string $audience = null;

    /**
     * @var string $algorithm
     */
    private string $algorithm = 'HS256';

    /**
     * @var string|KeySource $privateKey
     */
    private mixed $privateKey;

    /**
     * @var string|KeySource $publicKey
     */
    private mixed $publicKey;

    /**
     * @var ?int $duration
     */
    private ?int $duration = null;

    /**
     * @var ?int $delay
     */
    private ?int $delay = null;

    /**
     * @param ?array<string,mixed> $config
     */
    public function __construct(?array $config = null)
    {
        if(!is_null($config)) {
            $this->hydrate($config);
        }
    }

    /**
     * @param array<string,mixed> $config
     * @return void
     */
    protected function hydrate(array $config): void
    {
        if(array_key_exists('header', $config)) {
            $this->setHeader($config['header']);
        }
        
        if(array_key_exists('pattern', $config)) {
            $this->setPattern($config['pattern']);
        }
        
        if(array_key_exists('issuer', $config)) {
            $this->setIssuer($config['issuer']);
        }
        
        if(array_key_exists('subject', $config)) {
            $this->setSubject($config['subject']);
        }
        
        if(array_key_exists('audience', $config)) {
            $this->setAudience($config['audience']);
        }
        
        if(array_key_exists('algorithm', $config)) {
            $this->setAlgorithm($config['algorithm']);
        }
        
        if(array_key_exists('key', $config)) {
            $this->setKey($config['key']);
        }
        
        if(array_key_exists('publicKey', $config)) {
            $this->setPublicKey($config['publicKey']);
        }
        
        if(array_key_exists('privateKey', $config)) {
            $this->setPrivateKey($config['privateKey']);
        }
        
        if(array_key_exists('duration', $config)) {
            $this->setDuration($config['duration']);
        }
        
        if(array_key_exists('delay', $config)) {
            $this->setDelay($config['delay']);
        }
    }

    /**
     * @return string
     */
    public function getHeader(): string
    {
        return $this->header;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return ?string
     */
    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    /**
     * @return bool
     */
    public function hasIssuer(): bool
    {
        return !is_null($this->issuer);
    }

    /**
     * @return ?string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return bool
     */
    public function hasSubject(): bool
    {
        return !is_null($this->subject);
    }

    /**
     * @return ?string
     */
    public function getAudience(): ?string
    {
        return $this->audience;
    }

    /**
     * @return bool
     */
    public function hasAudience(): bool
    {
        return !is_null($this->audience);
    }

    /**
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }

    /**
     * @return string
     */
    public function getPublicKey(): mixed
    {
        return $this->publicKey instanceof KeySource
            ? $this->publicKey->resolve()
            : $this->publicKey;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): mixed
    {
        return $this->privateKey instanceof KeySource
            ? $this->privateKey->resolve()
            : $this->privateKey;
    }

    /**
     * @return ?int
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @return bool
     */
    public function hasDuration(): bool
    {
        return !is_null($this->duration);
    }

    /**
     * @return ?int
     */
    public function getDelay(): ?int
    {
        return $this->delay;
    }

    /**
     * @return bool
     */
    public function hasDelay(): bool
    {
        return !is_null($this->delay);
    }

    /**
     * @param ?string $value
     * @return void
     */
    public function setIssuer(?string $value): void
    {
        $this->issuer = $value;
    }

    /**
     * @param ?string $value
     * @return void
     */
    public function setSubject(?string $value): void
    {
        $this->subject = $value;
    }

    /**
     * @param ?string $value
     * @return void
     */
    public function setAudience(?string $value): void
    {
        $this->audience = $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setHeader(string $value): void
    {
        $this->header = $value;
    }

    /**
     * @param string $pattern
     * @return void
     */
    public function setPattern(string $pattern): void
    {
        $this->pattern = $pattern;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setAlgorithm(string $value): void
    {
        $this->algorithm = $value;
    }

    /**
     * @param string|KeySource $value
     * @return void
     */
    public function setPublicKey(mixed $value): void
    {
        $this->publicKey = $this->resolveKey($value);
    }

    /**
     * @param string|KeySource $value
     * @return void
     */
    public function setPrivateKey(mixed $value): void
    {
        $this->privateKey = $this->resolveKey($value);
    }

    /**
     * @param string|KeySource $value
     * @return void
     */
    public function setKey(mixed $value): void
    {
        $key = $this->resolveKey($value);
        $this->publicKey = $key;
        $this->privateKey = $key;
    }

    /**
     * @param string|int $value
     * @return void
     */
    public function setDuration(string|int $value): void
    {
        if(is_numeric($value)) {
            $this->duration = intval($value);
        }

        elseif(is_string($value) && false !== ($timed = strtotime($value))) {
            $this->duration = $timed - time();
        }

        else {
            $type = typeof($value);
            throw new ConfigException("Could not resolve '{$type}' type for config 'duration'");
        }
    }

    /**
     * @param string|int $value
     * @return void
     */
    public function setDelay(string|int $value): void
    {
        if(is_numeric($value)) {
            $this->delay = intval($value);
        }

        elseif(is_string($value) && false !== ($timed = strtotime($value))) {
            $this->delay = $timed - time();
        }
        
        else {
            $type = typeof($value);
            throw new ConfigException("Could not resolve '{$type}' type for config 'delay'");
        }
    }

    /**
     * @param string|KeySource $value
     * @return string
     */
    private function resolveKey(mixed $value): mixed
    {
        return is_string($value) ? $value : $value->resolve();
    }
}