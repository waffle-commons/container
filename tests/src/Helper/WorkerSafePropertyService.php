<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

use IgorPhp\IgorBundle\Attribute\WorkerSafe;

/**
 * Holds a mutable property that is explicitly audited as boot-frozen via
 * #[WorkerSafe]; the scanner must treat it as exempt (parity with the Igor audit).
 */
final class WorkerSafePropertyService
{
    #[WorkerSafe(reason: 'boot-frozen test fixture; never mutated per request')]
    public int $counter = 0;
}
