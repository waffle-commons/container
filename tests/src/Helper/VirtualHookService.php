<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

/**
 * Carries a virtual (storage-less) hooked property over readonly backing fields;
 * the virtual property has no backing state to leak, so the scanner must pass it.
 */
final class VirtualHookService
{
    public string $full {
        get => $this->first . ' ' . $this->last;
    }

    public function __construct(
        private readonly string $first = 'first',
        private readonly string $last = 'last',
    ) {}
}
