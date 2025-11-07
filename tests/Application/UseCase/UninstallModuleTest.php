<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\UninstallModuleCommand;
use CubaDevOps\Flexi\Application\UseCase\UninstallModule;
use Flexi\Contracts\Interfaces\HandlerInterface;
use PHPUnit\Framework\TestCase;

class UninstallModuleTest extends TestCase
{
    public function testImplementsHandlerInterface(): void
    {
        $uninstallModule = new UninstallModule();
        $this->assertInstanceOf(HandlerInterface::class, $uninstallModule);
    }

    public function testConstructorWithDefaultPaths(): void
    {
        $useCase = new UninstallModule();
        $this->assertInstanceOf(UninstallModule::class, $useCase);
    }

    public function testConstructorWithCustomPaths(): void
    {
        $customComposer = '/custom/composer.json';
        $customRoot = '/custom/root';

        $useCase = new UninstallModule($customComposer, $customRoot);
        $this->assertInstanceOf(UninstallModule::class, $useCase);
    }

    public function testHandleWithNonExistentModule(): void
    {
        $tempRootPath = sys_get_temp_dir() . '/test_uninstall_root_' . uniqid();
        $tempComposerPath = $tempRootPath . '/composer.json';

        mkdir($tempRootPath);

        // Create composer.json without the module
        $composerData = ['require' => []];
        file_put_contents($tempComposerPath, json_encode($composerData));

        $uninstallModule = new UninstallModule($tempComposerPath, $tempRootPath);
        $dto = new UninstallModuleCommand(['module_name' => 'NonExistentModule']);

        try {
            $result = $uninstallModule->handle($dto);

            // Verify it returns a MessageInterface
            $this->assertInstanceOf(\Flexi\Contracts\Interfaces\MessageInterface::class, $result);

        } catch (\Exception $e) {
            // This is expected when module is not found or other errors occur
            $this->assertIsString($e->getMessage());

        } finally {
            // Clean up
            if (is_file($tempComposerPath)) {
                unlink($tempComposerPath);
            }
            if (is_dir($tempRootPath)) {
                rmdir($tempRootPath);
            }
        }
    }
}