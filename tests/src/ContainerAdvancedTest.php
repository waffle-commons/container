<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Waffle\Commons\Container\Autowire;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Container\Exception\ContainerException;
use Waffle\Commons\Contracts\Service\ResettableInterface;
use WaffleTests\Commons\Container\Helper\ServiceWithIntersectionParam;
use WaffleTests\Commons\Container\Helper\ServiceWithoutDependencies;
use WaffleTests\Commons\Container\Helper\ServiceWithUnionAndDefault;
use WaffleTests\Commons\Container\Helper\ServiceWithUnionParam;
use WaffleTests\Commons\Container\Helper\ServiceWithUnresolvableUnion;
use WaffleTests\Commons\Container\Helper\ServiceWithVariadicParam;

#[CoversClass(Container::class)]
#[CoversClass(Autowire::class)]
final class ContainerAdvancedTest extends AbstractTestCase
{
    public function testLockedContainerRejectsNewRegistrations(): void
    {
        $container = new Container();
        $container->lock();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('container is locked after boot');

        $container->set('any.id', ServiceWithoutDependencies::class);
    }

    public function testCoreServiceCannotBeOverriddenOnceRegistered(): void
    {
        $container = new Container();
        // First registration of the core service id is allowed.
        $container->set(PsrContainerInterface::class, new ServiceWithoutDependencies());

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot override core service');

        // Second registration of the same core service id must be rejected.
        $container->set(PsrContainerInterface::class, new ServiceWithoutDependencies());
    }

    public function testResetInvokesResetOnResettableInstances(): void
    {
        $resettable = new class implements ResettableInterface {
            public int $resetCount = 0;

            #[\Override]
            public function reset(): void
            {
                $this->resetCount++;
            }
        };

        $container = new Container();
        $container->set('svc.resettable', $resettable);
        // Force instantiation so the instance is recorded internally.
        $container->get('svc.resettable');
        $container->get('svc.resettable');

        $container->reset();

        static::assertSame(1, $resettable->resetCount);
    }

    public function testAutowireSkipsVariadicParameters(): void
    {
        $container = new Container();

        $service = $container->get(ServiceWithVariadicParam::class);

        static::assertInstanceOf(ServiceWithVariadicParam::class, $service);
        // Variadic parameter was skipped (cannot be autowired as a service list).
        static::assertSame([], $service->rest);
    }

    public function testAutowireRejectsIntersectionTypes(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('intersection type');

        $container->get(ServiceWithIntersectionParam::class);
    }

    public function testAutowireResolvesUnionTypeWithFirstRegisteredCandidate(): void
    {
        $container = new Container();

        $service = $container->get(ServiceWithUnionParam::class);

        // First candidate (ServiceWithoutDependencies) is instantiable → wins.
        static::assertInstanceOf(ServiceWithUnionParam::class, $service);
        static::assertInstanceOf(ServiceWithoutDependencies::class, $service->dep);
    }

    public function testAutowireFallsBackToUnionDefaultWhenAllCandidatesUnresolvable(): void
    {
        $container = new Container();

        $service = $container->get(ServiceWithUnionAndDefault::class);

        static::assertInstanceOf(ServiceWithUnionAndDefault::class, $service);
        static::assertNull($service->dep);
    }

    public function testAutowireThrowsWhenUnionTypeUnresolvableAndNoDefault(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('union type could not be resolved');

        $container->get(ServiceWithUnresolvableUnion::class);
    }
}
