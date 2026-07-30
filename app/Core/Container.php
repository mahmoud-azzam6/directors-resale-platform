<?php

declare(strict_types=1);

namespace App\Core;

use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

final class Container
{
    /**
     * @var array<string, callable|string>
     */
    private array $bindings = [];

    /**
     * @var array<string, callable|string>
     */
    private array $singletons = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * @param callable|string|null $concrete
     */
    public function bind(string $abstract, $concrete = null): void
    {
        $this->bindings[$abstract] = $concrete ?? $abstract;
    }

    /**
     * @param callable|string|null $concrete
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->singletons[$abstract] = $concrete ?? $abstract;
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * @return mixed
     */
    public function make(string $abstract)
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->singletons[$abstract])) {
            $this->instances[$abstract] = $this->resolve($this->singletons[$abstract]);

            return $this->instances[$abstract];
        }

        $concrete = $this->bindings[$abstract] ?? $abstract;

        return $this->resolve($concrete);
    }

    /**
     * @param callable|string $concrete
     * @return mixed
     */
    private function resolve($concrete)
    {
        if (is_callable($concrete)) {
            return $concrete($this);
        }

        if (! class_exists($concrete)) {
            throw new RuntimeException("Class {$concrete} cannot be resolved.");
        }

        $reflection = new ReflectionClass($concrete);

        if (! $reflection->isInstantiable()) {
            throw new RuntimeException("Class {$concrete} is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $dependencies[] = $this->make($type->getName());

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();

                continue;
            }

            throw new RuntimeException("Parameter {$parameter->getName()} cannot be resolved.");
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
