<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog\Config;

use SlimEdge\Exceptions\ConfigException;

class Request
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
     * @var bool $logQuery
     */
    public bool $logQuery = true;

    /**
     * @var bool $logFormData
     */
    public bool $logFormData = true;

    /**
     * @var bool $logBody
     */
    public bool $logBody = true;

    /**
     * @var bool $logUploadedFiles
     */
    public bool $logUploadedFiles = true;

    /**
     * @var ?string[] $methods
     */
    public ?array $methods = null;

    /**
     * @var ?string[] $ignoreMethods
     */
    public ?array $ignoreMethods = null;

    /**
     * @var ?string[] $headers
     */
    public ?array $headers = null;

    /**
     * @var ?string[] $ignoreHeaders
     */
    public ?array $ignoreHeaders = null;

    /**
     * @var ?string[] $routes
     */
    public ?array $routes = null;

    /**
     * @var ?string[] $ignoreRoutes
     */
    public ?array $ignoreRoutes = null;

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
    private function hydrate(array $config): void
    {
        if(isset($config['ignoreOnMax'])) {
            if(!is_bool($config['ignoreOnMax'])) {
                $type = typeof($config['ignoreOnMax']);
                throw new ConfigException("Could not resolve {$type} for 'ignoreOnMax' value in HttpLog/Request config.");
            }

            $this->ignoreOnMax = $config['ignoreOnMax'];
        }
        
        if(isset($config['logQuery'])) {
            if(!is_bool($config['logQuery'])) {
                $type = typeof($config['logQuery']);
                throw new ConfigException("Could not resolve {$type} for 'logQuery' value in HttpLog/Request config.");
            }

            $this->logQuery = $config['logQuery'];
        }
        
        if(isset($config['logFormData'])) {
            if(!is_bool($config['logFormData'])) {
                $type = typeof($config['logFormData']);
                throw new ConfigException("Could not resolve {$type} for 'logFormData' value in HttpLog/Request config.");
            }

            $this->logFormData = $config['logFormData'];
        }
        
        if(isset($config['logBody'])) {
            if(!is_bool($config['logBody'])) {
                $type = typeof($config['logBody']);
                throw new ConfigException("Could not resolve {$type} for 'logBody' value in HttpLog/Request config.");
            }

            $this->logBody = $config['logBody'];
        }
        
        if(isset($config['logUploadedFiles'])) {
            if(!is_bool($config['logUploadedFiles'])) {
                $type = typeof($config['logUploadedFiles']);
                throw new ConfigException("Could not resolve {$type} for 'logUploadedFiles' value in HttpLog/Request config.");
            }

            $this->logUploadedFiles = $config['logUploadedFiles'];
        }

        if(isset($config['maxBody'])) {
            $raw = $config['maxBody'];
            if(is_int($raw) || is_float($raw) || is_string($raw))
                $resolved = intval($raw);
            else {
                $type = typeof($raw);
                throw new ConfigException("Could not resolve {$type} for 'maxBody' value in HttpLog/Request config.");
            }

            if($resolved < 0) {
                throw new ConfigException("Invalid value for 'maxBody' in HttpLog/Request config. Value must be positive integer.");
            }

            $this->maxBody = $resolved;
        }

        if($methods = $this->getConfigItemArray($config, 'methods')) {
            $this->methods = $methods;
        }

        if($addMethods = $this->getConfigItemArray($config, 'addMethods')) {
            if(is_array($this->methods)) {
                $this->methods = array_merge($this->methods, $addMethods);
            }
            else {
                $this->methods = $addMethods;
            }
        }

        if($ignoreMethods = $this->getConfigItemArray($config, 'ignoreMethods')) {
            $ignoreMethods = array_map('strtoupper', $ignoreMethods);
            if(is_array($this->methods)) {
                $methods = [];
                foreach($this->methods as $method) {
                    if(!in_array($method, $ignoreMethods)) {
                        $methods[] = $method;
                    }
                }

                $this->methods = $methods;
            }
            elseif(is_array($this->ignoreMethods)) {
                $this->ignoreMethods = array_merge($this->ignoreMethods, $ignoreMethods);
            }
            else $this->ignoreMethods = $ignoreMethods;
        }

        if($headers = $this->getConfigItemArray($config, 'headers')) {
            $this->headers = array_map([$this, 'normalizeHeader'], $headers);
        }

        if($addHeaders = $this->getConfigItemArray($config, 'addHeaders')) {
            $addHeaders = array_map([$this, 'normalizeHeader'], $addHeaders);
            if(is_array($this->headers)) {
                $this->headers = array_merge($this->headers, $addHeaders);
            }
            else {
                $this->headers = $addHeaders;
            }
        }

        if($ignoreHeaders = $this->getConfigItemArray($config, 'ignoreHeaders')) {
            $ignoreHeaders = array_map([$this, 'normalizeHeader'], $headers);
            if(is_array($this->headers)) {
                $headers = [];
                foreach($this->headers as $header) {
                    if(!in_array($header, $config)) {
                        $headers[] = $header;
                    }
                }

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
            if($this->routes) {
                $routes = [];
                foreach($this->routes as $route) {
                    if(!in_array($route, $ignoreRoutes)) {
                        $routes[] = $route;
                    }
                }

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
        if(is_array($this->methods)) {
            $this->methods = array_unique($this->methods);
        }
        
        if(is_array($this->ignoreMethods)) {
            $this->ignoreMethods = array_unique($this->ignoreMethods);
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
     * @param string $method
     * @return bool
     */
    public function checkMethod(string $method): bool
    {
        if(!is_null($this->methods) && !in_array($method, $this->methods)) {
            return false;
        }

        if(!is_null($this->ignoreMethods) && in_array($method, $this->ignoreMethods)) {
            return false;
        }

        return true;
    }
}
