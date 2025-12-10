<?php

declare(strict_types=1);

namespace Waffle\Commons\Container\Exception;

use Waffle\Commons\Contracts\Container\Exception\NotFoundExceptionInterface;

/**
 * Thrown when a requested entry was not found in the container.
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{
}
