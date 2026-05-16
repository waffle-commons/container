<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container\Helper;

interface MarkerA {}

interface MarkerB {}

final class ServiceWithIntersectionParam
{
    public function __construct(MarkerA&MarkerB $dep) {}
}
