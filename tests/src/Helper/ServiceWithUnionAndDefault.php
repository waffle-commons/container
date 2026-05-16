<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

final class ServiceWithUnionAndDefault
{
    public ?ServiceWithoutDependencies $dep;

    public function __construct(UnregisteredOne|UnregisteredTwo|null $dep = null)
    {
        $this->dep = null;
    }
}
