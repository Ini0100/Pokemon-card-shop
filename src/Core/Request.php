<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, mixed> $files
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $body,
        private readonly array $files,
    ) {
    }

    public static function fromGlobals(string $basePath): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        $path = '/' . trim($path, '/');

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path === '/index.php' ? '/' : $path,
            $_GET,
            $_POST,
            $_FILES,
        );
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    /** Reads a value from the POST body, falling back to the query string. */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = filter_var($this->input($key), FILTER_VALIDATE_INT);

        return $value === false ? $default : $value;
    }

    public function float(string $key): ?float
    {
        $value = filter_var($this->input($key), FILTER_VALIDATE_FLOAT);

        return $value === false ? null : $value;
    }

    /** @return list<int> */
    public function intList(string $key): array
    {
        $values = $this->input($key, []);

        if (!is_array($values)) {
            return [];
        }

        return array_values(array_filter(
            array_map('intval', $values),
            static fn (int $value): bool => $value > 0,
        ));
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->query;
    }

    /** @return array<string, mixed>|null */
    public function file(string $key): ?array
    {
        $file = $this->files[$key] ?? null;

        return is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE ? $file : null;
    }
}
