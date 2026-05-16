<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

final class ServiceWithUnionParam
{
    public ServiceWithoutDependencies|ServiceWithDefaultParam $dep;

    public function __construct(ServiceWithoutDependencies|ServiceWithDefaultParam $dep)
    {
        $this->dep = $dep;
    }
}
