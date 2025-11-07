<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\InstallModuleCommand;
use CubaDevOps\Flexi\Application\UseCase\InstallModule;
use Flexi\Contracts\Interfaces\HandlerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class InstallModuleTest extends TestCase
{
    private InstallModule $installModule;
    private string $tempModulesPath;
    private string $tempComposerPath;
    private string $tempRootPath;

    public function setUp(): void
    {
        $this->tempModulesPath = sys_get_temp_dir() . '/test_install_modules_' . uniqid();
        $this->tempRootPath = sys_get_temp_dir() . '/test_install_root_' . uniqid();
        $this->tempComposerPath = $this->tempRootPath . '/composer.json';

        mkdir($this->tempModulesPath);
        mkdir($this->tempRootPath);

        $this->installModule = new InstallModule(
            $this->tempModulesPath,
            $this->tempComposerPath,
            $this->tempRootPath
        );
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->tempModulesPath);
        $this->removeDirectory($this->tempRootPath);
    }

    public function testImplementsHandlerInterface(): void
    {
        $this->assertInstanceOf(HandlerInterface::class, $this->installModule);
    }

    public function testConstructorWithDefaultPaths(): void
    {
        $useCase = new InstallModule();
        $this->assertInstanceOf(InstallModule::class, $useCase);
    }

    public function testConstructorWithCustomPaths(): void
    {
        $customModules = '/custom/modules';
        $customComposer = '/custom/composer.json';
        $customRoot = '/custom/root';

        $useCase = new InstallModule($customModules, $customComposer, $customRoot);
        $this->assertInstanceOf(InstallModule::class, $useCase);
    }

    public function testHandleThrowsExceptionWhenModuleNotFound(): void
    {
        $dto = new InstallModuleCommand(['module_name' => 'NonExistentModule']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'NonExistentModule' not found in {$this->tempModulesPath}");

        $this->installModule->handle($dto);
    }

    public function testHandleThrowsExceptionWhenModuleHasNoComposerJson(): void
    {
        $moduleName = 'BrokenModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        $dto = new InstallModuleCommand(['module_name' => $moduleName]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'BrokenModule' has no composer.json");

        $this->installModule->handle($dto);
    }

    public function testHandleThrowsExceptionWhenModuleComposerHasNoName(): void
    {
        $moduleName = 'InvalidModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        // Create composer.json without name field
        $composerData = ['description' => 'Module without name'];
        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData)
        );

        $dto = new InstallModuleCommand(['module_name' => $moduleName]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'InvalidModule' composer.json has no 'name' field");

        $this->installModule->handle($dto);
    }

    public function testHandleWithModuleAlreadyInstalled(): void
    {
        $moduleName = 'ExistingModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        // Create valid module composer.json
        $moduleComposer = [
            'name' => 'cubadevops/flexi-module-existing',
            'version' => '1.0.0'
        ];
        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($moduleComposer)
        );

        // Create main composer.json with module already installed
        $mainComposer = [
            'require' => [
                'cubadevops/flexi-module-existing' => '@dev'
            ]
        ];
        file_put_contents($this->tempComposerPath, json_encode($mainComposer));

        $dto = new InstallModuleCommand(['module_name' => $moduleName]);
        $result = $this->installModule->handle($dto);

        $response = json_decode($result->get('body'), true);

        $this->assertTrue($response['success']);
        $this->assertStringContainsString('already installed', $response['message']);
        $this->assertEquals('cubadevops/flexi-module-existing', $response['package']);
        $this->assertEquals('none', $response['action']);
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}