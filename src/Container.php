<?php

declare(strict_types=1);

namespace Waffle\Commons\Container;

use Closure;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use Waffle\Commons\Container\Exception\ContainerException;
use Waffle\Commons\Container\Exception\NotFoundException;

final class Container implements ContainerInterface
{
    /** @var array<string, object> Cached instances of services */
    private array $instances = [];

    /** @var array<string, string|Closure|object|callable> Definitions of services */
    private array $definitions = [];

    /** @var array<string, true> Stack of services currently being resolved (for circular dependency detection) */
    private array $resolving = [];

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws ContainerException Error while retrieving the entry.
     * @throws NotFoundException  No entry was found for this identifier.
     */
    public function get(string $id): mixed
    {
        // 1. Return cached instance if available
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Check for circular dependency
        if (isset($this->resolving[$id])) {
            throw new ContainerException("Circular dependency detected while resolving service \"{$id}\".");
        }

        $this->resolving[$id] = true;

        try {
            // 3. Build and cache the instance
            $instance = $this->build($id);
            $this->instances[$id] = $instance;
        } finally {
            unset($this->resolving[$id]);
        }

        return $instance;
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     */
    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    /**
     * Registers a service or a factory in the container.
     *
     * @param string $id The service identifier (usually FQCN).
     * @param string|callable|object $concrete The concrete implementation or factory.
     */
    public function set(string $id, object|callable|string $concrete): void
    {
        $this->definitions[$id] = $concrete;
    }

    /**
     * Builds an instance of the service.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function build(string $id): object
    {
        $concrete = $this->definitions[$id] ?? $id;

        // Handle closures/factories
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }

        // Handle objects (already instantiated)
        if (is_object($concrete)) {
            return $concrete;
        }

        // Handle class strings (Autowiring)
        if (is_string($concrete) && class_exists($concrete)) {
            return $this->autowire($concrete);
        }

        throw new NotFoundException("Service or class \"{$id}\" not found.");
    }

    /**
     * Resolves a class using reflection (Autowiring).
     *
     * @param class-string $class
     * @throws ContainerException
     */
    private function autowire(string $class): object
    {
        try {
            $reflector = new ReflectionClass($class);

            if (!$reflector->isInstantiable()) {
                throw new ContainerException("Class \"{$class}\" is not instantiable.");
            }

            $constructor = $reflector->getConstructor();

            // If no constructor, simple instantiation
            if (null === $constructor) {
                return $reflector->newInstance();
            }

            // Resolve dependencies
            $dependencies = $this->resolveDependencies($constructor->getParameters());

            return $reflector->newInstanceArgs($dependencies);
        } catch (ReflectionException $e) {
            throw new ContainerException("Failed to autowire class \"{$class}\": " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param ReflectionParameter[] $parameters
     * @return array<int, mixed>
     * @throws ContainerException
     */
    private function resolveDependencies(array $parameters): array
    {
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            // Case 1: No type or primitive type -> Use default value if available
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new ContainerException("Cannot resolve primitive parameter \"{$parameter->getName()}\".");
            }

            // Case 2: Class/Interface type -> Recursively resolve from container
            /** @var string $name */
            $name = $type->getName();

            try {
                $dependencies[] = $this->get($name);
            } catch (NotFoundException $e) {
                // If not found but optional (nullable), allow null
                if ($parameter->allowsNull()) {
                    $dependencies[] = null;
                    continue;
                }
                throw new ContainerException(
                    "Dependency \"{$name}\" required by parameter \"{$parameter->getName()}\" could not be resolved.",
                    0,
                    $e,
                );
            }
        }

        return $dependencies;
    }
}
