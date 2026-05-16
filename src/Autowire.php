<?php

declare(strict_types=1);

namespace Waffle\Commons\Container;

use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Waffle\Commons\Container\Exception\ContainerException;
use Waffle\Commons\Container\Exception\NotFoundException;
use Waffle\Commons\Contracts\Container\ContainerInterface;

class Autowire
{
    private ContainerInterface $container;

    public function load(ContainerInterface $container, string $class): null|object|string
    {
        $this->container = $container;

        return $this->autowire(class: $class);
    }

    /**
     * Resolves a class using reflection (Autowiring).
     *
     * @param class-string $class
     * @throws ContainerException|ReflectionException
     */
    private function autowire(string $class): null|object|string
    {
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
            // Skip variadic parameters — the container cannot collect variadic service lists
            if ($parameter->isVariadic()) {
                continue;
            }

            $type = $parameter->getType();

            // Intersection types (A&B) are not supported for autowiring
            if ($type instanceof ReflectionIntersectionType) {
                throw new ContainerException(sprintf(
                    'Parameter "%s" uses an intersection type which cannot be autowired.',
                    $parameter->getName(),
                ));
            }

            // Union types (A|B): try each non-builtin type left-to-right, use first successful resolution
            if ($type instanceof ReflectionUnionType) {
                $resolved = false;

                foreach ($type->getTypes() as $unionType) {
                    if (!$unionType instanceof ReflectionNamedType || $unionType->isBuiltin()) {
                        continue;
                    }

                    try {
                        $dependencies[] = $this->container->get(id: $unionType->getName());
                        $resolved = true;
                        break;

                        // @mago-ignore lint:no-empty-catch-clause -- intentional: try next candidate in the union left-to-right
                    } catch (NotFoundException) {
                    }
                }

                if (!$resolved) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $dependencies[] = $parameter->getDefaultValue();
                        continue;
                    }

                    throw new ContainerException(sprintf(
                        'Parameter "%s" with union type could not be resolved: no candidate type is registered or instantiable.',
                        $parameter->getName(),
                    ));
                }

                continue;
            }

            // Case 1: No type or primitive (builtin) type — use default value if available
            if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new ContainerException("Cannot resolve primitive parameter \"{$parameter->getName()}\".");
            }

            // Case 2: Named class/interface type — recursively resolve from container
            $name = $type->getName();

            try {
                $dependencies[] = $this->container->get(id: $name);
            } catch (NotFoundException $e) {
                // If the parameter is nullable and the dependency cannot be found, inject null
                if ($parameter->allowsNull()) {
                    $dependencies[] = null;
                    continue;
                }

                // Non-nullable dependency could not be resolved — surface a descriptive error
                throw new ContainerException(
                    sprintf(
                        'Dependency "%s" for parameter "%s" could not be resolved: %s',
                        $name,
                        $parameter->getName(),
                        $e->getMessage(),
                    ),
                    previous: $e,
                );
            }
        }

        return $dependencies;
    }
}
