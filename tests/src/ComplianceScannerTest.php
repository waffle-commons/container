<?php

declare(strict_types=1);

namespace WaffleTests\Commons\Container;

use PHPUnit\Framework\Attributes\CoversClass;
use Waffle\Commons\Container\Compliance\ComplianceScanner;
use Waffle\Commons\Container\Container;
use Waffle\Commons\Container\Exception\ComplianceException;
use WaffleTests\Commons\Container\Helper\CompliantResettableService;
use WaffleTests\Commons\Container\Helper\NonCompliantMutableService;
use WaffleTests\Commons\Container\Helper\ReadonlyOnlyService;
use WaffleTests\Commons\Container\Helper\VirtualHookService;
use WaffleTests\Commons\Container\Helper\WorkerSafeClassService;
use WaffleTests\Commons\Container\Helper\WorkerSafePropertyService;

#[CoversClass(ComplianceScanner::class)]
#[CoversClass(ComplianceException::class)]
#[CoversClass(Container::class)]
final class ComplianceScannerTest extends AbstractTestCase
{
    public function testResettableServiceIsCompliant(): void
    {
        $this->expectNotToPerformAssertions();

        new ComplianceScanner()->scan(['svc' => new CompliantResettableService()]);
    }

    public function testReadonlyOnlyServiceIsCompliant(): void
    {
        $this->expectNotToPerformAssertions();

        new ComplianceScanner()->scan(['svc' => new ReadonlyOnlyService()]);
    }

    public function testWorkerSafePropertyIsExempt(): void
    {
        $this->expectNotToPerformAssertions();

        new ComplianceScanner()->scan(['svc' => new WorkerSafePropertyService()]);
    }

    public function testWorkerSafeClassIsExempt(): void
    {
        $this->expectNotToPerformAssertions();

        new ComplianceScanner()->scan(['svc' => new WorkerSafeClassService()]);
    }

    public function testVirtualHookPropertyIsExempt(): void
    {
        $this->expectNotToPerformAssertions();

        new ComplianceScanner()->scan(['svc' => new VirtualHookService()]);
    }

    public function testNonObjectAndNullEntriesAreIgnored(): void
    {
        $this->expectNotToPerformAssertions();

        new ComplianceScanner()->scan(['str' => 'a-service-id', 'null' => null]);
    }

    public function testNonCompliantServiceThrowsNamingClassAndProperty(): void
    {
        try {
            new ComplianceScanner()->scan(['svc' => new NonCompliantMutableService()]);
            self::fail('Expected ComplianceException');
        } catch (ComplianceException $exception) {
            self::assertStringContainsString(
                NonCompliantMutableService::class . '::$counter',
                $exception->getMessage(),
            );
            self::assertStringContainsString('ResettableInterface', $exception->getMessage());
        }
    }

    public function testAggregatesEveryOffenderInOneThrow(): void
    {
        try {
            new ComplianceScanner()->scan([
                'a' => new NonCompliantMutableService(),
                'b' => new NonCompliantMutableService(),
            ]);
            self::fail('Expected ComplianceException');
        } catch (ComplianceException $exception) {
            self::assertSame(2, substr_count($exception->getMessage(), '::$counter'));
        }
    }

    public function testLockWithStrictScanThrowsOnNonCompliantService(): void
    {
        $container = new Container(strictComplianceScan: true);
        $container->set('svc', new NonCompliantMutableService());

        $this->expectException(ComplianceException::class);
        $container->lock();
    }

    public function testLockWithStrictScanPassesForCompliantService(): void
    {
        $this->expectNotToPerformAssertions();

        $container = new Container(strictComplianceScan: true);
        $container->set('svc', new CompliantResettableService());
        $container->lock();
    }

    public function testLockWithoutStrictScanSkipsTheScanInProduction(): void
    {
        $this->expectNotToPerformAssertions();

        // Default (false) ⇒ no reflection cost, no throw even with a leaky service.
        $container = new Container();
        $container->set('svc', new NonCompliantMutableService());
        $container->lock();
    }
}
