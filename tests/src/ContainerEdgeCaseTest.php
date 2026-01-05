<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Container\Exception\ContainerException;
use Waffle\Commons\Container\Exception\NotFoundException;

/**
 * Targets edge cases in Dependency Injection logic:
 * - Circular dependencies detection
 * - Unresolvable parameters (scalars, union types)
 * - Instantiation restrictions (private constructors)
 */
#[CoversClass(Container::class)]
class ContainerEdgeCaseTest extends TestCase
{
    public function testGetThrowsNotFoundExceptionForNonExistentService(): void
    {
        $container = new Container([]);

        $this->expectException(NotFoundException::class);
        // Message typically contains "not found"
        $container->get('NonExistentService_' . uniqid());
    }

    public function testHasReturnsCorrectly(): void
    {
        $container = new Container([]);

        // 1. Autowiring Check: The container SHOULD report true for an existing, instantiable class
        // We use stdClass as a universally available simple class.
        static::assertTrue(
            $container->has(\stdClass::class),
            'Container::has() should return true for instantiable classes (autowiring).',
        );

        // 2. Non-Existent Check: Random strings should return false
        static::assertFalse(
            $container->has('non_existent_key_' . uniqid()),
            'Container::has() should return false for non-existent services.',
        );
    }

    public function testCircularDependencyThrowsException(): void
    {
        // Setup: ClassA depends on ClassB, ClassB depends on ClassA.
        // This creates an infinite recursion loop if not detected.

        $container = new Container([]);

        $this->expectException(ContainerException::class);
        // The container should detect the loop and throw, rather than crashing with memory error.
        $container->get(CircularA::class);
    }

    public function testUnresolvablePrimitiveParameterThrowsException(): void
    {
        // Class requires an int but no default value is provided
        $container = new Container([]);

        $this->expectException(ContainerException::class);
        $container->get(ClassWithPrimitive::class);
    }

    public function testPrivateConstructorThrowsException(): void
    {
        // Class has private constructor, cannot be instantiated via 'new'
        $container = new Container([]);

        $this->expectException(ContainerException::class);
        $container->get(ClassWithPrivateConstructor::class);
    }

    public function testComplexUnionTypeWithoutDefaultThrowsException(): void
    {
        // Class requires int|string, container cannot decide which one to inject
        $container = new Container([]);

        $this->expectException(ContainerException::class);
        $container->get(ClassWithUnionType::class);
    }
}

// --- HELPER CLASSES FOR TESTS ---

class CircularA
{
    public function __construct(CircularB $_b) {}
}

class CircularB
{
    public function __construct(CircularA $_a) {}
}

class ClassWithPrimitive
{
    public function __construct(int $_id) {}
}

class ClassWithPrivateConstructor
{
    private function __construct() {}
}

class ClassWithUnionType
{
    public function __construct(int|string $_val) {}
}
