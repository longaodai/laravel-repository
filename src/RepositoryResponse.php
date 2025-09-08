<?php

declare(strict_types=1);

namespace LongAoDai\Repository;

use Illuminate\Support\Collection;
use stdClass;

/**
 * Class RepositoryResponse
 *
 * A standardized data transfer object between Service ↔ Repository layers.
 * It normalizes input (Collection | array | stdClass | null) into arrays
 * and provides helper methods to access and manipulate data and options.
 *
 * @package LongAoDai\Repository
 */
final class RepositoryResponse
{
    /**
     * Main payload data.
     *
     * @var array<string, mixed>
     */
    private array $data;

    /**
     * Additional metadata or options.
     *
     * @var array<string, mixed>
     */
    private array $options;

    /**
     * RepositoryResponse constructor.
     *
     * @param Collection|array<string, mixed>|stdClass|null $data
     * @param Collection|array<string, mixed>|stdClass|null $options
     */
    public function __construct(
        Collection|array|stdClass|null $data = null,
        Collection|array|stdClass|null $options = null
    )
    {
        $this->data = $this->normalize($data);
        $this->options = $this->normalize($options);
    }

    /**
     * Normalize parameter into array format.
     *
     * @param Collection|array<string, mixed>|stdClass|null $param
     *
     * @return array<string, mixed>
     */
    private function normalize(Collection|array|stdClass|null $param): array
    {
        return match (true) {
            $param instanceof Collection => $param->toArray(),
            $param instanceof stdClass => (array)$param,
            is_array($param) => $param,
            default => [],
        };
    }

    /**
     * Get value(s) from the main data.
     *
     * @param string|null $key If null, return the entire data array.
     *
     * @return mixed
     */
    public function get(?string $key = null): mixed
    {
        return $key !== null
            ? ($this->data[$key] ?? null)
            : (!empty($this->data) ? $this->data : null);
    }

    /**
     * Get value(s) from the options metadata.
     *
     * @param string|null $key If null, return the entire options array.
     *
     * @return mixed
     */
    public function option(?string $key = null): mixed
    {
        return $key !== null
            ? ($this->options[$key] ?? null)
            : (!empty($this->options) ? $this->options : null);
    }

    /**
     * Return all payload (data + options).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return [
            'data' => $this->get(),
            'options' => $this->option(),
        ];
    }

    /**
     * Get only specific keys from the main data.
     *
     * @param array<int, string> $keys
     *
     * @return array<string, mixed>|null
     */
    public function only(array $keys = []): ?array
    {
        if (empty($keys)) {
            return $this->get();
        }

        $results = [];

        foreach ($keys as $key) {
            $results[$key] = $this->get($key);
        }

        return $results;
    }

    /**
     * Set or merge new value(s) into the main data.
     *
     * @param string|array<string, mixed> $key
     * @param mixed|null $value
     *
     * @return array<string, mixed>
     */
    public function set(string|array $key, mixed $value = null): array
    {
        if (is_array($key)) {
            return $this->setData($key);
        }

        $this->data[$key] = $value;

        return $this->data;
    }

    /**
     * Merge multiple values into the main data.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function setData(array $data): array
    {
        $this->data = array_merge($this->data, $data);
        return $this->data;
    }

    /**
     * Set or merge new value(s) into the options metadata.
     *
     * @param string|array<string, mixed> $key
     * @param mixed|null $value
     *
     * @return array<string, mixed>
     */
    public function setOption(string|array $key, mixed $value = null): array
    {
        if (is_array($key)) {
            return $this->setOptions($key);
        }

        $this->options[$key] = $value;

        return $this->options;
    }

    /**
     * Merge multiple values into the options metadata.
     *
     * @param array<string, mixed> $options
     *
     * @return array<string, mixed>
     */
    private function setOptions(array $options): array
    {
        $this->options = array_merge($this->options, $options);
        return $this->options;
    }
}
