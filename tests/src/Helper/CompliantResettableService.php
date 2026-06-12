<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

use Waffle\Commons\Contracts\Service\ResettableInterface;

/**
 * Mutable shared service that IS compliant: it resets its state per request.
 */
final class CompliantResettableService implements ResettableInterface
{
    public int $counter = 0;

    #[\Override]
    public function reset(): void
    {
        $this->counter = 0;
    }
}
