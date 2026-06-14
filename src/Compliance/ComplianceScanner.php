<?php

declare(strict_types=1);

namespace Waffle\Commons\Container\Compliance;

use IgorPhp\IgorBundle\Attribute\WorkerSafe;
use ReflectionObject;
use ReflectionProperty;
use Waffle\Commons\Container\Exception\ComplianceException;
use Waffle\Commons\Contracts\Service\ResettableInterface;

/**
 * DIAG-02 boot-time state-reset compliance scanner.
 *
 * Reflects every shared (singleton) service the container holds and fails the
 * boot if one keeps mutable instance state yet does NOT implement
 * {@see ResettableInterface} — in resident-worker mode that state would leak
 * across requests. A property is considered safe when it is readonly, static, a
 * virtual (storage-less) hook, or explicitly marked `#[WorkerSafe]`; a whole
 * class marked `#[WorkerSafe]` is exempt. Honouring `#[WorkerSafe]` keeps this
 * gate aligned with the Igor audit instead of contradicting it.
 *
 * Dev-only and stateless: the container constructs it (and runs it) solely when
 * strict compliance scanning is enabled, so production pays nothing.
 */
#[WorkerSafe(reason: 'dev-only boot-time reflection; never runs in production and holds no per-request state')]
final class ComplianceScanner
{
    /**
     * @param array<string, null|object|string> $instances Shared singletons held by the container.
     *
     * @throws ComplianceException When a non-resettable shared service holds mutable instance state.
     */
    public function scan(array $instances): void
    {
        $offenders = [];

        foreach ($instances as $service) {
            if (!is_object($service) || $service instanceof ResettableInterface) {
                continue;
            }

            $reflection = new ReflectionObject($service);
            if ($reflection->getAttributes(WorkerSafe::class) !== []) {
                continue;
            }

            foreach ($reflection->getProperties() as $property) {
                if ($this->isImmutable($property) || $property->getAttributes(WorkerSafe::class) !== []) {
                    continue;
                }

                $offenders[] = sprintf('%s::$%s', $service::class, $property->getName());
            }
        }

        if ($offenders !== []) {
            throw ComplianceException::fromOffenders($offenders);
        }
    }

    /**
     * A property cannot leak request state when it is readonly, static, or a
     * virtual hook with no backing storage.
     */
    private function isImmutable(ReflectionProperty $property): bool
    {
        return $property->isReadOnly() || $property->isStatic() || $property->isVirtual();
    }
}
