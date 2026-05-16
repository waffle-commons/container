<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

final class ServiceWithVariadicParam
{
    /** @var array<array-key, ServiceWithoutDependencies> */
    public array $rest;

    public function __construct(ServiceWithoutDependencies $first, ServiceWithoutDependencies ...$rest)
    {
        $this->rest = $rest;
    }
}
