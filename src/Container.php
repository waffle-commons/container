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
        // Return the memoised instance first — a cache hit is the hot path and
        // must short-circuit before any resolution work.
        //
        // Beta-1 fix: the previous implementation stored this decision in a
        // `$this->checks` instance flag that was set on the first cache hit and
        // never cleared. Because the Container is a resident-worker singleton,
        // that flag stayed `true` for the life of the worker and made every later
        // *uncached* get() return `$this->instances[$id]` (i.e. null) instead of
        // building the service — a cross-request contamination bug.
        if (array_key_exists($id, $this->instances)) {
            return $this->instances[$id];
        }

        // Beta-1 hardening (audit: "Eliminate Exceptions for Control Flow"):
        // fail fast on unknown identifiers so callers can use `has()` as a
        // cheap precondition instead of catching NotFoundException downstream.
        if (!$this->has($id)) {
            throw new NotFoundException("Service or class \"{$id}\" not found.");
        }

        // Circular-dependency guard: `$id` is already mid-resolution up the stack.
        if (array_key_exists($id, $this->resolving)) {
            throw new ContainerException("Circular dependency detected while resolving service \"{$id}\".");
        }

        // @igor-ignore: transient circular-dependency guard, removed in the finally block within this same call.
        $this->resolving[$id] = true;
        try {
            $instance = $this->build($id);
            // @igor-ignore: intentional worker-lifetime memoization of DI singletons; not request-scoped state.
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
        return array_key_exists($id, $this->definitions) || class_exists($id);
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

        // @igor-ignore: boot-time service registration; the registry is frozen by lock() before any request is handled.
        $this->definitions[$id] = $concrete;
    }

    /**
     * Locks the container to prevent further service registration.
     * Call this after the boot sequence is complete.
     */
    public function lock(): void
    {
        // @igor-ignore: one-shot boot latch flipped once after wiring; never mutated per request.
        $this->locked = true;
    }

    /**
     * Builds an instance of the service.
     *
     * @throws ContainerException
     * @throws NotFoundException
     */
    private function build(string $id): object|string|null
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
            return new Autowire(container: $this)->load(class: $concrete);
        }

        throw new NotFoundException("Service or class \"{$id}\" not found.");
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
