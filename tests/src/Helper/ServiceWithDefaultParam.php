<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

class ServiceWithDefaultParam
{
    public function __construct(
        public int $value = 42,
    ) {}
}
