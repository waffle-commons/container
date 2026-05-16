<?php

declare(strict_types=1);

namespace Waffle\Commons\Container;

use Closure;
use Waffle\Commons\Container\Exception\ContainerException;
use Waffle\Commons\Container\Exception\NotFoundException;
use Waffle\Commons\Contracts\Container\ContainerInterface;
use Waffle\Commons\Contracts\Service\ResettableInterface;

final class Container implements ContainerInterface
{
    /** @var array<string, null|object|string> Cached instances of services */
    private array $instances = [];

    /** @var array<string, string|Closure|object|callable> Definitions of services */
    private array $definitions = [];

    /** @var array<string, true> Stack of services currently being resolved (for circular dependency detection) */
    private array $resolving = [];

    /** Prevents overriding core services after the container is locked */
    private bool $locked = false;

    /** Prevents overriding core services after the container is locked */
    private bool $checks = false;

    /** @var array<string, true> Core service identifiers that must never be overridden */
    private const CORE_SERVICES = [
        \Psr\Container\ContainerInterface::class => true,
    ];

    /**
     * @param array<string, string|Closure|object|callable> $definitions Pre-loaded service definitions.
     */
    public function __construct(array $definitions = [])
    {
        $this->definitions = $definitions;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     * @return mixed Entry.
     * @throws ContainerException Error while retrieving the entry.
     * @throws NotFoundException  No entry was found for this identifier.
     */
    #[\Override]
    public function get(string $id): mixed
    {
        $this->performChecks(id: $id);

        if ($this->checks) {
            return $this->instances[$id];
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
    #[\Override]
    public function has(string $id): bool
    {
        // @mago-ignore lint:no-isset
        return isset($this->definitions[$id]) || class_exists($id);
    }

    /**
     * Registers a service or a factory in the container.
     *
     * @param string $id The service identifier (usually FQCN).
     * @param string|callable|object $concrete The concrete implementation or factory.
     * @throws ContainerException If the container is locked or if attempting to override a core service.
     */
    #[\Override]
    public function set(string $id, object|callable|string $concrete): void
    {
        if ($this->locked) {
            throw new ContainerException(sprintf('Cannot register service "%s": container is locked after boot.', $id));
        }

        if ((self::CORE_SERVICES[$id] ?? null) !== null && ($this->definitions[$id] ?? null) !== null) {
            throw new ContainerException(sprintf('Cannot override core service "%s".', $id));
        }

        $this->definitions[$id] = $concrete;
    }

    /**
     * Locks the container to prevent further service registration.
     * Call this after the boot sequence is complete.
     */
    public function lock(): void
    {
        $this->locked = true;
    }

    /**
     * Builds an instance of the service.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function build(string $id): null|object|string
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
            return new Autowire()->load(container: $this, class: $concrete);
        }

        throw new NotFoundException("Service or class \"{$id}\" not found.");
    }

    private function performChecks(string $id): void
    {
        // 1. Return cached instance if available
        if (array_key_exists(key: $id, array: $this->instances) && $this->instances[$id]) {
            $this->checks = true;
        }

        // 2. Check for circular dependency
        if (array_key_exists(key: $id, array: $this->resolving) && $this->resolving[$id]) {
            throw new ContainerException("Circular dependency detected while resolving service \"{$id}\".");
        }
    }

    /**
     * Clean all stateful services
     * This method is called by the Kernel at the end of each worker loop
     */
    public function reset(): void
    {
        foreach ($this->instances as $_ => $service) {
            if (!$service instanceof ResettableInterface) {
                continue;
            }

            $service->reset();
        }
    }
}
