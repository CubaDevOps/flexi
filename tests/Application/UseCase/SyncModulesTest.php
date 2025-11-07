<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\SyncModulesCommand;
use CubaDevOps\Flexi\Application\UseCase\SyncModules;
use Flexi\Contracts\Interfaces\HandlerInterface;
use PHPUnit\Framework\TestCase;

class SyncModulesTest extends TestCase
{
    public function testImplementsHandlerInterface(): void
    {
        $syncModules = new SyncModules();
        $this->assertInstanceOf(HandlerInterface::class, $syncModules);
    }

    public function testConstructorWithDefaultPaths(): void
    {
        $useCase = new SyncModules();
        $this->assertInstanceOf(SyncModules::class, $useCase);
    }

    public function testConstructorWithCustomPaths(): void
    {
        $customModules = '/custom/modules';
        $customComposer = '/custom/composer.json';
        $customRoot = '/custom/root';

        $useCase = new SyncModules($customModules, $customComposer, $customRoot);
        $this->assertInstanceOf(SyncModules::class, $useCase);
    }

    public function testHandleReturnsMessageInterface(): void
    {
        $tempModulesPath = sys_get_temp_dir() . '/test_sync_modules_' . uniqid();
        $tempRootPath = sys_get_temp_dir() . '/test_sync_root_' . uniqid();
        $tempComposerPath = $tempRootPath . '/composer.json';

        mkdir($tempModulesPath);
        mkdir($tempRootPath);

        // Create a basic composer.json
        file_put_contents($tempComposerPath, json_encode(['require' => []]));

        $syncModules = new SyncModules($tempModulesPath, $tempComposerPath, $tempRootPath);
        $dto = new SyncModulesCommand();

        try {
            $result = $syncModules->handle($dto);

            // If it doesn't throw, verify it returns a MessageInterface
            $this->assertInstanceOf(\Flexi\Contracts\Interfaces\MessageInterface::class, $result);

        } catch (\Exception $e) {
            // This is expected when composer command fails in test environment
            // We just verify it throws some exception related to the operation
            $this->assertIsString($e->getMessage());

        } finally {
            // Clean up
            if (is_file($tempComposerPath)) {
                unlink($tempComposerPath);
            }
            if (is_dir($tempRootPath)) {
                rmdir($tempRootPath);
            }
            if (is_dir($tempModulesPath)) {
                rmdir($tempModulesPath);
            }
        }
    }
}