<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\ModuleStatusCommand;
use CubaDevOps\Flexi\Application\UseCase\GetModuleStatus;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleState;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleType;
use CubaDevOps\Flexi\Infrastructure\Factories\ModuleDetectorInterface;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GetModuleStatusTest extends TestCase
{
    /** @var ModuleStateManagerInterface&MockObject */
    private $stateManager;
    /** @var ModuleDetectorInterface&MockObject */
    private $moduleDetector;
    private GetModuleStatus $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->stateManager = $this->createMock(ModuleStateManagerInterface::class);
        $this->moduleDetector = $this->createMock(ModuleDetectorInterface::class);
        $this->useCase = new GetModuleStatus($this->stateManager, $this->moduleDetector);
    }

    public function testHandleReturnsErrorWhenModuleMissing(): void
    {
        $command = new ModuleStatusCommand(['module_name' => 'analytics']);

        $this->stateManager
            ->expects($this->once())
            ->method('getModuleState')
            ->with('analytics')
            ->willReturn(null);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertFalse($payload['success']);
        $this->assertSame("Module 'analytics' not found", $payload['error']);
    }

    public function testHandleReturnsSpecificModuleWithDetails(): void
    {
        $command = new ModuleStatusCommand([
            'module_name' => 'analytics',
            'details' => true,
        ]);

        $state = new ModuleState(
            'analytics',
            true,
            ModuleType::mixed(),
            new DateTimeImmutable('2025-01-01T10:00:00+00:00'),
            'tester',
            [
                'package' => 'flexi/analytics',
                'version' => '1.0.0',
                'path' => '/modules/analytics',
                'has_conflict' => true,
                'local_path' => '/modules/analytics',
                'vendor_path' => '/vendor/flexi/analytics',
                'resolution_strategy' => 'local_priority',
            ]
        );

        $this->stateManager->method('getModuleState')->with('analytics')->willReturn($state);
        $this->stateManager->method('isModuleActive')->with('analytics')->willReturn(true);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertTrue($payload['success']);
        $this->assertSame('analytics', $payload['module']['name']);
        $this->assertSame('active', $payload['module']['status']);
        $this->assertSame('mixed', $payload['module']['type']);
        $this->assertSame('flexi/analytics', $payload['module']['package']);
        $this->assertSame('1.0.0', $payload['module']['version']);
        $this->assertSame('/modules/analytics', $payload['module']['path']);
        $this->assertSame('tester', $payload['module']['details']['modified_by']);
        $this->assertTrue($payload['module']['details']['has_conflict']);
        $this->assertSame('/modules/analytics', $payload['module']['conflict_info']['local_path']);
    }

    public function testHandleListsAllModulesWithConflictsAndDetails(): void
    {
        $command = new ModuleStatusCommand([
            'details' => true,
            'conflicts' => true,
        ]);

        $alphaInfo = new ModuleInfo('alpha', 'flexi/alpha', ModuleType::local(), '/modules/alpha', '1.0.0', true);
        $betaInfo = new ModuleInfo(
            'beta',
            'flexi/beta',
            ModuleType::mixed(),
            '/modules/beta',
            '2.0.0',
            false,
            [
                'local_path' => '/modules/beta',
                'vendor_path' => '/vendor/flexi/beta',
                'resolution_strategy' => 'manual',
            ]
        );

        $alphaState = new ModuleState('alpha', true, ModuleType::local(), new DateTimeImmutable('2025-01-02T10:00:00+00:00'), 'ops');
        $betaState = new ModuleState('beta', false, ModuleType::mixed(), new DateTimeImmutable('2025-01-03T12:00:00+00:00'), 'qa');

        $this->moduleDetector->method('getAllModules')->willReturn([$alphaInfo, $betaInfo]);
        $this->moduleDetector->method('getModuleStatistics')->willReturn(['total' => 2]);

        $this->stateManager
            ->method('isModuleActive')
            ->willReturnMap([
                ['alpha', true],
                ['beta', false],
            ]);

        $this->stateManager
            ->method('getModuleState')
            ->willReturnMap([
                ['alpha', $alphaState],
                ['beta', $betaState],
            ]);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertTrue($payload['success']);
        $this->assertSame(2, $payload['summary']['total_modules']);
        $this->assertSame(1, $payload['summary']['active']);
        $this->assertSame(1, $payload['summary']['inactive']);
        $this->assertSame(['beta'], $payload['conflicts_found']);
        $this->assertCount(2, $payload['modules']);
        $this->assertSame('alpha', $payload['modules'][0]['name']);
        $this->assertSame('beta', $payload['modules'][1]['name']);
        $this->assertSame('manual', $payload['modules'][1]['conflict']['resolution_strategy']);
    }

    public function testHandleAppliesTypeFilter(): void
    {
        $command = new ModuleStatusCommand(['type' => 'vendor']);

        $localModule = new ModuleInfo('local', 'flexi/local', ModuleType::local(), '/modules/local');
        $vendorModule = new ModuleInfo('remote', 'flexi/remote', ModuleType::vendor(), '/vendor/flexi/remote');

        $this->moduleDetector->method('getAllModules')->willReturn([$localModule, $vendorModule]);
        $this->moduleDetector->method('getModuleStatistics')->willReturn(['types' => ['local' => 1, 'vendor' => 1]]);
        $this->stateManager->method('isModuleActive')->willReturn(true);
        $this->stateManager->method('getModuleState')->willReturn(null);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertTrue($payload['success']);
        $this->assertSame(1, $payload['summary']['total_modules']);
        $this->assertCount(1, $payload['modules']);
        $this->assertSame('remote', $payload['modules'][0]['name']);
        $this->assertSame(['type' => 'vendor'], $payload['filter']);
    }

    private function decode($message): array
    {
        $payload = json_decode($message->get('body'), true);
        $this->assertIsArray($payload);
        return $payload;
    }
}
