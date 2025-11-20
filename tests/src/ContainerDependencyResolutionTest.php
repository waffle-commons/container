<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Container\Exception\ContainerException;

/**
 * Specifically targets the 'resolveDependencies' logic (and ReflectionTrait)
 * to ensure all exception branches for parameter resolution are covered.
 */
#[CoversClass(Container::class)]
class ContainerDependencyResolutionTest extends TestCase
{
    /**
     * Scenario: Constructor has a parameter with NO type hint and NO default value.
     * The container cannot guess what to inject.
     */
    public function testResolveDependenciesFailsOnUntypedParameter(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/(untyped|resolve|parameter)/i');

        $container->get(ClassWithUntypedParameter::class);
    }

    /**
     * Scenario: Constructor requires an Intersection Type (A&B).
     * Most containers do not support resolving intersection types automatically.
     */
    public function testResolveDependenciesFailsOnIntersectionType(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        // The error message usually mentions that intersection types are not supported
        // or that it cannot resolve the parameter.

        $container->get(ClassWithIntersectionType::class);
    }

    /**
     * Scenario: Dependency chain failure.
     * Class A depends on B, but B has an unresolvable dependency.
     * This ensures the exception bubbles up correctly through the recursive resolution calls.
     */
    public function testResolveDependenciesBubblesExceptionFromChild(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);

        // Chain: Parent -> Child -> Unresolvable
        $container->get(ClassWithBrokenDependency::class);
    }

    /**
     * Scenario: Trying to instantiate an Abstract Class directly.
     * This might fail at instantiation or validation before dependency resolution.
     */
    public function testGetThrowsExceptionForAbstractClass(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessageMatches('/(abstract|instantiable)/i');

        $container->get(AbstractServiceClass::class);
    }
}

// --- HELPER CLASSES ---

class ClassWithUntypedParameter {
    // Container sees $param but no type -> Exception
    public function __construct($param) {}
}

interface InterfaceA {}
interface InterfaceB {}

class ClassWithIntersectionType {
    // Container cannot find ONE service that implies BOTH A and B automatically
    public function __construct(InterfaceA&InterfaceB $service) {}
}

class ClassWithBrokenDependency {
    public function __construct(ClassWithUntypedParameter $child) {}
}

abstract class AbstractServiceClass {
    public function __construct() {}
}