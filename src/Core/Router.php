<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Maps HTTP method + path patterns (e.g. `/products/{id}`) to controller actions.
 * Route parameters are passed to the action as integers.
 */
final class Router
{
    /** @var array<string, list<array{pattern: string, handler: array{class-string, string}}>> */
    private array $routes = [];

    public function __construct(private readonly Container $container)
    {
    }

    /** @param array{class-string, string} $handler */
    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param array{class-string, string} $handler */
    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes[$request->method] ?? [] as $route) {
            if (preg_match($route['pattern'], $request->path, $matches)) {
                [$class, $method] = $route['handler'];
                $params = array_map('intval', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

                $this->container->get($class)->$method($request, ...$params);

                return;
            }
        }

        throw HttpException::notFound();
    }

    /** @param array{class-string, string} $handler */
    private function add(string $method, string $path, array $handler): void
    {
        $pattern = preg_replace('#\{(\w+)\}#', '(?P<$1>\d+)', $path);

        $this->routes[$method][] = [
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler,
        ];
    }
}
