<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

use Psr\Container\ContainerInterface;

class ServiceWithNullableParam
{
    // Dependency injection of an interface that might not exist in container
    public function __construct(
        public null|ContainerInterface $container = null,
    ) {}
}
