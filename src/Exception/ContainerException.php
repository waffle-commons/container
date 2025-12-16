<?php

declare(strict_types=1);

namespace Waffle\Commons\Container\Exception;

use Exception;
use Waffle\Commons\Contracts\Container\Exception\ContainerExceptionInterface;

/**
 * Base exception for all container-related errors.
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{
}
