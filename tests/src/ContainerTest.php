<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Container\Exception\ContainerException;
use Waffle\Commons\Container\Exception\NotFoundException;
use WaffleTests\Commons\Container\Helper\ServiceWithDefaultParam;
use WaffleTests\Commons\Container\Helper\ServiceWithDependencies;
use WaffleTests\Commons\Container\Helper\ServiceWithNullableParam;
use WaffleTests\Commons\Container\Helper\ServiceWithoutDependencies;
use WaffleTests\Commons\Container\Helper\ServiceWithUnresolvableParam;

// Local helper for uninstantiable class test
abstract class AbstractHelperClass
{
}

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testImplementsPsrInterface(): void
    {
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    public function testSetAndGetSimpleService(): void
    {
        $service = new \stdClass();
        $this->container->set('my_service', $service);

        $this->assertSame($service, $this->container->get('my_service'));
    }

    public function testHasReturnsTrueForExistingService(): void
    {
        $this->container->set('my_service', new \stdClass());
        $this->assertTrue($this->container->has('my_service'));
    }

    public function testHasReturnsFalseForMissingService(): void
    {
        $this->assertFalse($this->container->has('non_existent_service'));
    }

    public function testGetThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);
        $this->container->get('non_existent_service');
    }

    public function testAutowiringSimpleClass(): void
    {
        // Teste l'instanciation d'une classe sans constructeur
        $instance = $this->container->get(\stdClass::class);
        $this->assertInstanceOf(\stdClass::class, $instance);
    }

    public function testAutowiringResolvesDependenciesRecursively(): void
    {
        // Teste resolveDependencies() avec une vraie classe
        $instance = $this->container->get(ServiceWithDependencies::class);

        $this->assertInstanceOf(ServiceWithDependencies::class, $instance);
        $this->assertInstanceOf(ServiceWithoutDependencies::class, $instance->service);
    }

    public function testAutowiringUsesDefaultValues(): void
    {
        // Teste la branche "isDefaultValueAvailable" de resolveDependencies
        $instance = $this->container->get(ServiceWithDefaultParam::class);

        $this->assertSame(42, $instance->value);
    }

    public function testAutowiringHandlesNullableDependencies(): void
    {
        // Teste la branche "allowsNull" + catch NotFoundException de resolveDependencies
        // L'interface ContainerInterface n'est pas définie dans le conteneur, donc get() lance NotFoundException.
        // Mais comme le paramètre est nullable, le conteneur doit injecter null.
        $instance = $this->container->get(ServiceWithNullableParam::class);

        $this->assertNull($instance->container);
    }

    public function testAutowiringHandlesNullableDependenciesWithoutDefault(): void
    {
        // This targets the specific branch where allowsNull() is true BUT no default value exists.
        // The dependency (UnboundInterface) is missing, so it catches NotFoundException.
        // Then it checks allowsNull() -> true.
        // Injects null.
        $instance = $this->container->get(ServiceWithNullableParamNoDefault::class);

        $this->assertNull($instance->dependency);
    }

    public function testAutowiringFailsForMissingDependency(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('could not be resolved');

        $this->container->get(ServiceWithMissingDependency::class);
    }

    public function testAutowiringThrowsExceptionForUnresolvableParameter(): void
    {
        // Teste l'échec de résolution d'une primitive sans défaut
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Cannot resolve primitive parameter "impossible"');

        $this->container->get(ServiceWithUnresolvableParam::class);
    }

    public function testAutowiringThrowsExceptionForUninstantiableClass(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('is not instantiable');

        // Use an abstract class instead of an interface.
        // class_exists returns true for abstract classes, so Container::build calls autowire(),
        // which then fails at $reflector->isInstantiable().
        $this->container->get(AbstractHelperClass::class);
    }

    public function testCircularDependencyDetection(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Circular dependency detected');

        // Create a cycle: Service A depends on B, B depends on A
        $this->container->set('A', fn(ContainerInterface $c) => $c->get('B'));
        $this->container->set('B', fn(ContainerInterface $c) => $c->get('A'));

        // Trigger the cycle
        $this->container->get('A');
    }
}

class ServiceWithNullableParamNoDefault
{
    public function __construct(
        public null|UnboundInterfaceForTest $dependency,
    ) {}
}

interface UnboundInterfaceForTest
{
}

class ServiceWithMissingDependency
{
    public function __construct(
        public \NonExistentClass $dependency,
    ) {}
}
