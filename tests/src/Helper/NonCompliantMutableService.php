<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

/**
 * Mutable shared service that is NOT compliant: it holds request-mutable state
 * but neither resets it nor marks it boot-frozen — the DIAG-02 target.
 */
final class NonCompliantMutableService
{
    public int $counter = 0;
}
