<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Container\Exception\ContainerException;
use Waffle\Commons\Container\Exception\NotFoundException;
use WaffleTests\Commons\Container\Helper\CompliantResettableService;
use WaffleTests\Commons\Container\Helper\ServiceWithoutDependencies;

/**
 * `memoise()` is the memo `get()` and a compiled container (AOT) share: an id
 * resolved through either path is one instance, which `reset()` covers.
 */
final class ContainerMemoiseTest extends TestCase
{
    public function testBuildsOnceAndGetReturnsTheSameInstance(): void
    {
        $container = new Container();
        $builds = 0;
        $build = static function () use (&$builds): ServiceWithoutDependencies {
            $builds++;

            return new ServiceWithoutDependencies();
        };

        $first = $container->memoise(ServiceWithoutDependencies::class, $build);
        $second = $container->memoise(ServiceWithoutDependencies::class, $build);

        static::assertSame($first, $second);
        static::assertSame($first, $container->get(ServiceWithoutDependencies::class));
        static::assertSame(1, $builds);
    }

    public function testReturnsTheInstanceGetBuiltWithoutBuildingAgain(): void
    {
        $container = new Container();
        $built = $container->get(ServiceWithoutDependencies::class);

        $memoised = $container->memoise(
            ServiceWithoutDependencies::class,
            static fn(): never => throw new RuntimeException('an id get() resolved is never built again'),
        );

        static::assertSame($built, $memoised);
    }

    public function testStaysOpenAfterLock(): void
    {
        $container = new Container();
        $container->lock();

        $service = $container->memoise(
            ServiceWithoutDependencies::class,
            static fn(): ServiceWithoutDependencies => new ServiceWithoutDependencies(),
        );

        static::assertSame($service, $container->get(ServiceWithoutDependencies::class));
    }

    public function testNeverAddsAnIdTheContainerCannotResolve(): void
    {
        // After lock(), set() refuses new services; memoise() must not be a way around it.
        $container = new Container();
        $container->lock();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Service or class "late" not found.');

        $container->memoise('late', static fn(): stdClass => new stdClass());
    }

    public function testRefusesACircularBuild(): void
    {
        $container = new Container();
        $container->set('loop', stdClass::class);

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected while resolving service "loop"');

        $container->memoise('loop', static fn(): object|string|null => $container->memoise(
            'loop',
            static fn(): stdClass => new stdClass(),
        ));
    }

    public function testReleasesTheGuardWhenTheBuildFails(): void
    {
        $container = new Container();
        $container->set('flaky', ServiceWithoutDependencies::class);
        $failure = '';
        try {
            $container->memoise('flaky', static fn(): never => throw new RuntimeException('boom'));
        } catch (RuntimeException $e) {
            $failure = $e->getMessage();
        }

        $service = $container->memoise(
            'flaky',
            static fn(): ServiceWithoutDependencies => new ServiceWithoutDependencies(),
        );

        static::assertSame('boom', $failure, 'the build failure reaches the caller');
        static::assertInstanceOf(ServiceWithoutDependencies::class, $service);
    }

    public function testResetCoversAMemoisedInstance(): void
    {
        $container = new Container();
        $service = $container->memoise(
            CompliantResettableService::class,
            static fn(): CompliantResettableService => new CompliantResettableService(),
        );
        static::assertInstanceOf(CompliantResettableService::class, $service);
        $service->counter = 3;

        $container->reset();

        static::assertSame(0, $service->counter);
    }
}
