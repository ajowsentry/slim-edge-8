<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Config;

use SlimEdge\Exceptions\ConfigException;

class Response
{
    use ConfigTrait;

    /**
     * @var ?int $maxBody
     */
    public ?int $maxBody = null;

    /**
     * @var bool $ignoreOnMax
     */
    public bool $ignoreOnMax = false;

    /**
     * @var bool $logBody
     */
    public bool $logBody = true;

    /**
     * @var ?list<string> $statusCodes
     */
    public ?array $statusCodes = null;

    /**
     * @var ?list<string|int> $ignoreStatusCodes
     */
    public ?array $ignoreStatusCodes = null;

    /**
     * @var ?list<string> $headers
     */
    public ?array $headers = null;

    /**
     * @var ?list<string> $ignoreHeaders
     */
    public ?array $ignoreHeaders = null;

    /**
     * @var ?list<string> $routes
     */
    public ?array $routes = null;

    /**
     * @var ?list<string> $ignoreRoutes
     */
    public $ignoreRoutes = null;

    /**
     * @param ?array<string,mixed> $config
     */
    public function __construct(?array $config = null)
    {
        if(!is_null($config)) {
            $this->hydrate($config);
            $this->cleanDuplicate();
        }
    }

    /**
     * @param array<string,mixed> $config
     * @return void
     */
    private function hydrate($config): void
    {
        if(isset($config['maxBody'])) {
            $raw = $config['maxBody'];
            if(is_int($raw) || is_float($raw) || is_string($raw))
                $resolved = intval($raw);
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'maxBody' value in HttpLog/Response config.");
            }

            if($resolved < 0) {
                throw new ConfigException("Invalid value for 'maxBody' in HttpLog/Response config. Value must be positive integer.");
            }

            $this->maxBody = $resolved;
        }

        if(isset($config['ignoreOnMax'])) {
            if(!is_bool($config['ignoreOnMax'])) {
                $type = typeof($config['ignoreOnMax']);
                throw new ConfigException("Could not resolve {$type} for 'ignoreOnMax' value in HttpLog/Response config.");
            }

            $this->ignoreOnMax = $config['ignoreOnMax'];
        }

        if($statusCodes = $this->getConfigItemArray($config, 'statusCodes')) {
            $this->statusCodes = $statusCodes;
        }

        if($addStatusCodes = $this->getConfigItemArray($config, 'addStatusCodes')) {
            if(is_array($this->statusCodes)) {
                $this->statusCodes = array_merge($this->statusCodes, $addStatusCodes);
            }
            else $this->statusCodes = $addStatusCodes;
        }

        if($ignoreStatusCodes = $this->getConfigItemArray($config, 'ignoreStatusCodes')) {
            if(is_array($this->statusCodes)) {
                $statusCodes = [];

                foreach($this->statusCodes as $method)
                    if(!in_array($method, $ignoreStatusCodes))
                        $statusCodes[] = $method;

                $this->statusCodes = $statusCodes;
            }
            elseif(is_array($this->ignoreStatusCodes)) {
                $this->ignoreStatusCodes = array_merge($this->ignoreStatusCodes, $ignoreStatusCodes);
            }
            else $this->ignoreStatusCodes = $ignoreStatusCodes;
        }

        if($headers = $this->getConfigItemArray($config, 'headers')) {
            $this->headers = array_map([$this, 'normalizeHeader'], $headers);
        }

        if($ignoreHeaders = $this->getConfigItemArray($config, 'ignoreHeaders')) {
            $ignoreHeaders = array_map([$this, 'normalizeHeader'], $ignoreHeaders);
            if(is_array($this->headers)) {
                $headers = [];

                foreach($this->headers as $header)
                    if(!in_array($header, $config))
                        $headers[] = $header;

                $this->headers = $headers;
            }
            elseif(is_array($this->ignoreHeaders)) {
                $this->ignoreHeaders = array_merge($this->ignoreHeaders, $ignoreHeaders);
            }
            else $this->ignoreHeaders = $ignoreHeaders;
        }

        if($routes = $this->getConfigItemArray($config, 'routes')) {
            $this->routes = $routes;
        }

        if($ignoreRoutes = $this->getConfigItemArray($config, 'ignoreRoutes')) {
            if(is_array($this->routes)) {
                $routes = [];
                foreach($this->routes as $route)
                    if(!in_array($route, $ignoreRoutes))
                        $routes[] = $route;

                $this->routes = $routes;
            }
            else $this->ignoreRoutes = $ignoreRoutes;
        }
    }

    /**
     * @return void
     */
    private function cleanDuplicate(): void
    {
        if(is_array($this->statusCodes)) {
            $this->statusCodes = array_unique($this->statusCodes);
        }

        if(is_array($this->ignoreStatusCodes)) {
            $this->ignoreStatusCodes = array_unique($this->ignoreStatusCodes);
        }

        if(is_array($this->headers)) {
            $this->headers = array_unique($this->headers);
        }

        if(is_array($this->ignoreHeaders)) {
            $this->ignoreHeaders = array_unique($this->ignoreHeaders);
        }

        if(is_array($this->routes)) {
            $this->routes = array_unique($this->routes);
        }

        if(is_array($this->ignoreRoutes)) {
            $this->ignoreRoutes = array_unique($this->ignoreRoutes);
        }
    }

    /**
     * @param int|string $statusCode
     * @return bool
     */
    public function checkStatusCode(int|string $statusCode): bool
    {
        if(!is_null($this->statusCodes)) {

            if(!in_array($statusCode, $this->statusCodes)) {
                return false;
            }
            
            if(!in_array(intdiv($statusCode, 100) . 'xx', $this->statusCodes)) {
                return false;
            }
        }

        if(!is_null($this->ignoreStatusCodes)) {

            if(in_array($statusCode, $this->ignoreStatusCodes)) {
                return false;
            }

            if(in_array(intdiv($statusCode, 100) . 'xx', $this->ignoreStatusCodes)) {
                return false;
            }
        }

        return true;
    }
}
