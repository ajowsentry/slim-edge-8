<?php

declare(strict_types=1);

namespace SlimEdge\HttpLog;

use DateTime;

final class LogData
{
    /**
     * @var array<string,mixed> $data
     */
    private array $data = [];

    /**
     * @param string $type
     * @param array<string,mixed> $payload
     */
    public function __construct(string $type, array $payload = [])
    {
        $datetime = new DateTime();
        $this->data['type'] = $type;

        $this->append('timestamp', get_timestamp($datetime));
        $this->append('datetime', $datetime->format(DateTime::RFC3339_EXTENDED));
        $this->appendAll($payload);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function append(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * @param array<string,mixed> $array
     * @return void
     */
    public function appendAll(array $array): void
    {
        foreach($array as $key => $value) {
            $this->append($key, $value);
        }
    }

    /**
     * Finish hashing and get data
     * @return array<string,mixed>
     */
    public function finish(): array
    {
        $this->data['hash'] = separate_string(ulid_generate(true), '-', 6, 4, 4, 4);
        return $this->data;
    }
}