<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

class ServiceWithoutDependencies
{
    public function hello(): string
    {
        return 'world';
    }
}
