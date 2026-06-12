<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

/**
 * Immutable shared service: only readonly properties, so nothing can leak.
 */
final readonly class ReadonlyOnlyService
{
    public function __construct(
        public string $name = 'service',
    ) {}
}
