<?php

declare(strict_types=1);

namespace App\Core;

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Small dependency-injection container with constructor autowiring.
 * Every resolved service is shared (singleton) for the duration of the request.
 */
final class Container
{
    /** @var array<string, Closure> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function set(string $id, Closure $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @return T
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        $object = isset($this->factories[$id])
            ? ($this->factories[$id])($this)
            : $this->autowire($id);

        return $this->instances[$id] = $object;
    }

    private function autowire(string $class): object
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Cannot resolve unknown service [{$class}].");
        }

        $reflection  = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException(
                    "Cannot autowire parameter \${$parameter->getName()} of [{$class}]."
                );
            }
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
