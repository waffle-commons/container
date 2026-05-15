<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

interface UnregisteredOne {}

interface UnregisteredTwo {}

final class ServiceWithUnresolvableUnion
{
    public function __construct(UnregisteredOne|UnregisteredTwo $dep) {}
}
