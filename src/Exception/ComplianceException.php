<?php

declare(strict_types=1);

namespace Waffle\Commons\Container\Exception;

use Waffle\Commons\Contracts\Service\ResettableInterface;

/**
 * Raised by the DIAG-02 boot-time compliance scanner when a shared (singleton)
 * service holds mutable instance state but cannot reset it between requests.
 *
 * In FrankenPHP resident-worker mode such state survives the request boundary and
 * bleeds across requests. Failing the boot (dev mode only) turns a silent
 * state-pollution bug into an immediate, actionable startup error.
 */
final class ComplianceException extends ContainerException
{
    /**
     * @param list<string> $offenders `Class::$property` entries that are mutable
     *        on a non-resettable shared service.
     */
    public static function fromOffenders(array $offenders): self
    {
        $lines = array_map(static fn(string $offender): string => '  - ' . $offender, $offenders);

        return new self(sprintf(
            'Stateless compliance violation: %d shared-service propert%s would leak across requests '
            . "in worker mode:\n%s\nImplement %s (a reset() that clears the state) or mark boot-frozen "
            . 'properties #[WorkerSafe].',
            count($offenders),
            count($offenders) === 1 ? 'y' : 'ies',
            implode("\n", $lines),
            ResettableInterface::class,
        ));
    }
}
