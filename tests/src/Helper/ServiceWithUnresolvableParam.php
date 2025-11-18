<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

class ServiceWithUnresolvableParam
{
    // No type hint, no default value -> Container can't guess
    public function __construct(
        public $impossible,
    ) {}
}
