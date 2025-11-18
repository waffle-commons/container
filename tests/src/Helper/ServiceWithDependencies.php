<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

class ServiceWithDependencies
{
    public function __construct(
        public ServiceWithoutDependencies $service,
    ) {}
}
