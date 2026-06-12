<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

use IgorPhp\IgorBundle\Attribute\WorkerSafe;

/**
 * Whole class audited as worker-safe via a class-level #[WorkerSafe]; the scanner
 * must skip it entirely even though it carries a mutable property.
 */
#[WorkerSafe(reason: 'audited test fixture; mutation is boot-time only')]
final class WorkerSafeClassService
{
    public int $counter = 0;
}
