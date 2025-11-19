<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\UpdateModuleEnvironmentCommand;
use CubaDevOps\Flexi\Application\UseCase\UpdateModuleEnvironment;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleType;
use CubaDevOps\Flexi\Infrastructure\Factories\HybridModuleDetector;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleEnvironmentManagerInterface;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateModuleEnvironmentTest extends TestCase
{
    /** @var ModuleEnvironmentManagerInterface&MockObject */
    private $envManager;
    /** @var HybridModuleDetector&MockObject */
    private $moduleDetector;
    /** @var ModuleStateManagerInterface&MockObject */
    private $stateManager;
    private UpdateModuleEnvironment $useCase;
    private ModuleInfo $moduleInfo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envManager = $this->createMock(ModuleEnvironmentManagerInterface::class);
        $this->moduleDetector = $this->createMock(HybridModuleDetector::class);
        $this->stateManager = $this->createMock(ModuleStateManagerInterface::class);

        $this->useCase = new UpdateModuleEnvironment($this->envManager, $this->moduleDetector, $this->stateManager);
        $this->moduleInfo = new ModuleInfo('analytics', 'flexi/analytics', ModuleType::local(), '/modules/analytics');
    }

    public function testHandleFailsWhenModuleNameMissing(): void
    {
        $payload = $this->decode($this->useCase->handle(new UpdateModuleEnvironmentCommand()));

        $this->assertFalse($payload['success']);
        $this->assertSame('Module name is required', $payload['error']);
    }

    public function testHandleFailsWhenModuleNotFound(): void
    {
        $command = new UpdateModuleEnvironmentCommand(['module_name' => 'analytics']);

        $this->moduleDetector
            ->expects($this->once())
            ->method('getModuleInfo')
            ->with('analytics')
            ->willReturn(null);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertFalse($payload['success']);
        $this->assertSame("Module 'analytics' not found. Use 'modules:list' to see available modules.", $payload['error']);
    }

    public function testHandleFailsWhenModuleInactive(): void
    {
        $command = new UpdateModuleEnvironmentCommand(['module_name' => 'analytics']);

        $this->moduleDetector->method('getModuleInfo')->willReturn($this->moduleInfo);
        $this->stateManager->method('isModuleActive')->with('analytics')->willReturn(false);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertFalse($payload['success']);
        $this->assertSame("Module 'analytics' is not active. Activate it first to update environment variables.", $payload['error']);
    }

    public function testHandleFailsWhenModuleHasNoEnvFile(): void
    {
        $command = new UpdateModuleEnvironmentCommand(['module_name' => 'analytics']);

        $this->moduleDetector->method('getModuleInfo')->willReturn($this->moduleInfo);
        $this->stateManager->method('isModuleActive')->willReturn(true);
        $this->envManager->method('hasModuleEnvFile')->with('/modules/analytics')->willReturn(false);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertFalse($payload['success']);
        $this->assertSame("Module 'analytics' does not have a .env file.", $payload['error']);
    }

    public function testHandleFailsWhenEnvFileEmpty(): void
    {
        $command = new UpdateModuleEnvironmentCommand(['module_name' => 'analytics']);

        $this->moduleDetector->method('getModuleInfo')->willReturn($this->moduleInfo);
        $this->stateManager->method('isModuleActive')->willReturn(true);
        $this->envManager->method('hasModuleEnvFile')->with('/modules/analytics')->willReturn(true);
        $this->envManager->method('readModuleEnvironment')->willReturn([]);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertFalse($payload['success']);
        $this->assertSame("Module 'analytics' .env file is empty or invalid.", $payload['error']);
    }

    public function testHandleFailsWhenUpdateFails(): void
    {
        $command = new UpdateModuleEnvironmentCommand(['module_name' => 'analytics']);

        $this->moduleDetector->method('getModuleInfo')->willReturn($this->moduleInfo);
        $this->stateManager->method('isModuleActive')->willReturn(true);
        $this->envManager->method('hasModuleEnvFile')->willReturn(true);
        $this->envManager->method('readModuleEnvironment')->willReturn(['TOKEN' => 'value']);
        $this->envManager->method('hasModuleEnvironment')->willReturn(false);
        $this->envManager->method('updateModuleEnvironment')->willReturn(false);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertFalse($payload['success']);
        $this->assertSame("Failed to update environment variables for module 'analytics'", $payload['error']);
    }

    public function testHandleUpdatesEnvironmentInPreserveMode(): void
    {
        $command = new UpdateModuleEnvironmentCommand([
            'module_name' => 'analytics',
            'modified_by' => 'tester',
        ]);

        $this->moduleDetector->method('getModuleInfo')->willReturn($this->moduleInfo);
        $this->stateManager->method('isModuleActive')->willReturn(true);
        $this->envManager->method('hasModuleEnvFile')->with('/modules/analytics')->willReturn(true);
        $this->envManager->method('readModuleEnvironment')->with('/modules/analytics', 'analytics')->willReturn([
            'TOKEN' => 'new',
            'API_KEY' => 'default',
        ]);
        $this->envManager->method('hasModuleEnvironment')->with('analytics')->willReturn(true);
        $this->envManager->method('getModuleEnvironment')->with('analytics')->willReturnOnConsecutiveCalls([
            'TOKEN' => 'old',
            'USER_VALUE' => 'keep',
        ],[
            'TOKEN' => 'new',
            'API_KEY' => 'default',
            'USER_VALUE' => 'keep',
        ]);

        $this->envManager
            ->expects($this->once())
            ->method('updateModuleEnvironment')
            ->with('analytics', ['TOKEN' => 'new', 'API_KEY' => 'default'])
            ->willReturn(true);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertTrue($payload['success']);
        $this->assertSame('preserve_user_changes', $payload['update_mode']);
        $this->assertSame(['TOKEN'], $payload['changes']['modified']);
        $this->assertSame(['API_KEY'], $payload['changes']['added']);
        $this->assertSame(['USER_VALUE'], $payload['changes']['preserved']);
        $this->assertArrayHasKey('TOKEN', $payload['modification_details']);
        $this->assertSame([
            'old' => 'old',
            'new' => 'new',
        ], $payload['modification_details']['TOKEN']);
    }

    public function testHandleUpdatesEnvironmentInForceMode(): void
    {
        $command = new UpdateModuleEnvironmentCommand([
            'module_name' => 'analytics',
            'force' => true,
        ]);

        $this->moduleDetector->method('getModuleInfo')->willReturn($this->moduleInfo);
        $this->stateManager->method('isModuleActive')->willReturn(true);
        $this->envManager->method('hasModuleEnvFile')->willReturn(true);
        $this->envManager->method('readModuleEnvironment')->willReturn(['TOKEN' => 'new']);

        $this->envManager
            ->expects($this->once())
            ->method('hasModuleEnvironment')
            ->with('analytics')
            ->willReturn(true);
        $this->envManager
            ->expects($this->once())
            ->method('getModuleEnvironment')
            ->with('analytics')
            ->willReturn(['TOKEN' => 'old', 'REMOVE_ME' => '1']);
        $this->envManager
            ->expects($this->once())
            ->method('removeModuleEnvironment')
            ->with('analytics')
            ->willReturn(true);
        $this->envManager
            ->expects($this->once())
            ->method('addModuleEnvironment')
            ->with('analytics', ['TOKEN' => 'new'])
            ->willReturn(true);

        $payload = $this->decode($this->useCase->handle($command));

        $this->assertTrue($payload['success']);
        $this->assertSame('force', $payload['update_mode']);
        $this->assertSame(2, $payload['details']['total_vars_before']);
        $this->assertSame(1, $payload['details']['modified_vars']);
        $this->assertSame(['REMOVE_ME'], $payload['changes']['removed']);
        $this->assertSame([], $payload['changes']['added']);
        $this->assertSame(['TOKEN'], $payload['changes']['modified']);
        $this->assertArrayNotHasKey('modification_details', $payload);
    }

    private function decode($message): array
    {
        $payload = json_decode($message->get('body'), true);
        $this->assertIsArray($payload);
        return $payload;
    }
}
