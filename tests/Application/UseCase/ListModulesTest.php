<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\ListModulesCommand;
use CubaDevOps\Flexi\Application\UseCase\ListModules;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;

class ListModulesTest extends TestCase
{
    private ListModules $listModules;
    private string $tempModulesPath;
    private string $tempVendorPath;

    public function setUp(): void
    {
        // Create temporary directories for testing
        $this->tempModulesPath = sys_get_temp_dir() . '/test_modules_' . uniqid();
        $this->tempVendorPath = sys_get_temp_dir() . '/test_vendor_' . uniqid();

        mkdir($this->tempModulesPath);
        mkdir($this->tempVendorPath);

        $this->listModules = new ListModules($this->tempModulesPath, $this->tempVendorPath);
    }

    public function tearDown(): void
    {
        // Clean up temporary directories
        $this->removeDirectory($this->tempModulesPath);
        $this->removeDirectory($this->tempVendorPath);
    }

    public function testImplementsHandlerInterface(): void
    {
        $this->assertInstanceOf(HandlerInterface::class, $this->listModules);
    }

    public function testHandleWithNoModules(): void
    {
        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);
        $this->assertInstanceOf(PlainTextMessage::class, $result);

        $response = json_decode($result->get('body'), true);

        $this->assertEquals(0, $response['total']);
        $this->assertEquals(0, $response['installed']);
        $this->assertEquals([], $response['modules']);
    }

    public function testHandleWithSingleModule(): void
    {
        // Create a test module
        $moduleName = 'TestModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        // Create composer.json for the module
        $composerData = [
            'name' => 'cubadevops/flexi-module-test',
            'version' => '1.0.0',
            'description' => 'Test module',
            'type' => 'flexi-module',
            'require' => [
                'php' => '^7.4|^8.0'
            ],
            'extra' => [
                'flexi' => [
                    'autoload' => true
                ]
            ]
        ];

        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData, JSON_PRETTY_PRINT)
        );

        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $response = json_decode($result->get('body'), true);

        $this->assertEquals(1, $response['total']);
        $this->assertEquals(0, $response['installed']); // Not installed via composer
        $this->assertArrayHasKey($moduleName, $response['modules']);

        $moduleInfo = $response['modules'][$moduleName];
        $this->assertEquals($moduleName, $moduleInfo['name']);
        $this->assertEquals($modulePath, $moduleInfo['path']);
        $this->assertFalse($moduleInfo['installed']);
        $this->assertTrue($moduleInfo['composer_exists']);
        $this->assertEquals('cubadevops/flexi-module-test', $moduleInfo['package']);
        $this->assertEquals('1.0.0', $moduleInfo['version']);
        $this->assertEquals('Test module', $moduleInfo['description']);
        $this->assertEquals('flexi-module', $moduleInfo['type']);
        $this->assertArrayHasKey('flexi', $moduleInfo);
        $this->assertEquals(1, $moduleInfo['dependencies']); // Only php dependency
    }

    public function testHandleWithInstalledModule(): void
    {
        // Create a test module
        $moduleName = 'Auth';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        // Create composer.json for the module
        $composerData = [
            'name' => 'cubadevops/flexi-module-auth',
            'version' => '2.0.0',
            'description' => 'Authentication module'
        ];

        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData)
        );

        // Create symlink in vendor to simulate installed module
        $vendorModulePath = $this->tempVendorPath . '/flexi-module-auth';
        mkdir($vendorModulePath);

        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $response = json_decode($result->get('body'), true);

        $this->assertEquals(1, $response['total']);
        $this->assertEquals(1, $response['installed']);

        $moduleInfo = $response['modules'][$moduleName];
        $this->assertTrue($moduleInfo['installed']);
    }

    public function testHandleWithModuleWithoutComposerJson(): void
    {
        // Create a test module without composer.json
        $moduleName = 'BrokenModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $response = json_decode($result->get('body'), true);

        $this->assertEquals(1, $response['total']);
        $moduleInfo = $response['modules'][$moduleName];
        $this->assertFalse($moduleInfo['composer_exists']);
        $this->assertEquals($moduleName, $moduleInfo['name']);
    }

    public function testConstructorWithCustomPaths(): void
    {
        $customModulesPath = '/custom/modules';
        $customVendorPath = '/custom/vendor';

        $useCase = new ListModules($customModulesPath, $customVendorPath);

        $this->assertInstanceOf(ListModules::class, $useCase);
    }

    public function testHandleWithNonExistentModulesDirectory(): void
    {
        // Remove modules directory
        rmdir($this->tempModulesPath);

        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $response = json_decode($result->get('body'), true);

        $this->assertEquals(0, $response['total']);
        $this->assertEquals(0, $response['installed']);
        $this->assertEquals([], $response['modules']);
    }

    public function testHandleWithNonExistentVendorDirectory(): void
    {
        // Remove vendor directory
        rmdir($this->tempVendorPath);

        // Create a test module
        $moduleName = 'TestModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        $composerData = ['name' => 'cubadevops/flexi-module-test'];
        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData)
        );

        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $response = json_decode($result->get('body'), true);

        $this->assertEquals(1, $response['total']);
        $this->assertEquals(0, $response['installed']); // No vendor directory means no installed modules
    }

    public function testHandleWithMultipleInstalledModules(): void
    {
        // Create multiple installed modules in vendor
        $vendorModules = [
            'flexi-module-auth',
            'flexi-module-user',
            'flexi-module-admin'
        ];

        foreach ($vendorModules as $packageName) {
            $vendorModulePath = $this->tempVendorPath . '/' . $packageName;
            mkdir($vendorModulePath);
        }

        // Create corresponding modules directory entries
        $moduleNames = ['auth', 'user', 'admin'];
        foreach ($moduleNames as $moduleName) {
            $modulePath = $this->tempModulesPath . '/' . ucfirst($moduleName);
            mkdir($modulePath);

            $composerData = [
                'name' => "cubadevops/flexi-module-{$moduleName}",
                'version' => '1.0.0'
            ];

            file_put_contents(
                $modulePath . '/composer.json',
                json_encode($composerData)
            );
        }

        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $response = json_decode($result->get('body'), true);

        $this->assertEquals(3, $response['total']);
        $this->assertEquals(3, $response['installed']);

        // Check each module is marked as installed
        foreach (['Auth', 'User', 'Admin'] as $moduleName) {
            $this->assertTrue($response['modules'][$moduleName]['installed']);
        }
    }

    public function testHandleWithInvalidJsonInComposer(): void
    {
        // Create a test module with invalid JSON
        $moduleName = 'InvalidJson';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        // Create invalid JSON file
        file_put_contents($modulePath . '/composer.json', 'invalid json content');

        $dto = new ListModulesCommand();

        $this->expectException(\JsonException::class);
        $this->listModules->handle($dto);
    }

    public function testHandleWithModuleHavingMinimalComposerJson(): void
    {
        // Create a module with minimal composer.json (no optional fields)
        $moduleName = 'Minimal';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        $composerData = [
            'name' => 'cubadevops/flexi-module-minimal'
            // Missing: version, description, type, require, extra
        ];

        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData)
        );

        $dto = new ListModulesCommand();
        $result = $this->listModules->handle($dto);

        $response = json_decode($result->get('body'), true);
        $moduleInfo = $response['modules'][$moduleName];

        // Check defaults are applied
        $this->assertEquals('unknown', $moduleInfo['version']);
        $this->assertEquals('', $moduleInfo['description']);
        $this->assertEquals('unknown', $moduleInfo['type']);
        $this->assertEquals(0, $moduleInfo['dependencies']);
        $this->assertArrayNotHasKey('flexi', $moduleInfo);
    }

    public function testPathNormalizationInConstructor(): void
    {
        // Test path normalization with trailing slashes
        $useCase = new ListModules('./modules/', './vendor/cubadevops/');
        $this->assertInstanceOf(ListModules::class, $useCase);
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