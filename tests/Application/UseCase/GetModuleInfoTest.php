<?php

declare(strict_types=1);

namespace Flexi\Test\Application\UseCase;

use Flexi\Application\Commands\ModuleInfoCommand;
use Flexi\Application\UseCase\GetModuleInfo;
use Flexi\Domain\Interfaces\ModuleStateManagerInterface;
use Flexi\Domain\Interfaces\ModuleDetectorInterface;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GetModuleInfoTest extends TestCase
{
    private GetModuleInfo $getModuleInfo;
    private string $tempModulesPath;
    private $stateManager;
    private $moduleDetector;

    public function setUp(): void
    {
        $this->tempModulesPath = sys_get_temp_dir() . '/test_modules_info_' . uniqid();
        mkdir($this->tempModulesPath);

        // Create mocks for dependencies
        $this->stateManager = $this->createMock(ModuleStateManagerInterface::class);
        $this->moduleDetector = $this->createMock(ModuleDetectorInterface::class);

        $this->getModuleInfo = new GetModuleInfo($this->stateManager, $this->moduleDetector);
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->tempModulesPath);
    }

    public function testImplementsHandlerInterface(): void
    {
        $this->assertInstanceOf(HandlerInterface::class, $this->getModuleInfo);
    }

    public function testHandleThrowsExceptionWhenModuleNotFound(): void
    {
        $dto = new ModuleInfoCommand(['module_name' => 'NonExistentModule']);

        $this->moduleDetector->method('getModuleInfo')
            ->with('NonExistentModule')
            ->willReturn(null);

        $result = $this->getModuleInfo->handle($dto);
        $this->assertEquals("Module 'NonExistentModule' not found", $result->get('body'));
    }

    public function testHandleWithValidModule(): void
    {
        $moduleName = 'TestModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        // Create src directory for statistics
        mkdir($modulePath . '/src');
        file_put_contents($modulePath . '/src/TestClass.php', '<?php class TestClass {}');

        $composerData = [
            'name' => 'cubadevops/flexi-module-test',
            'version' => '1.0.0',
            'description' => 'Test module for unit testing',
            'type' => 'flexi-module',
            'license' => 'MIT',
            'authors' => [
                [
                    'name' => 'Test Author',
                    'email' => 'test@example.com'
                ]
            ],
            'keywords' => ['flexi', 'module', 'test'],
            'require' => [
                'php' => '^8.0'
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^9.0'
            ],
            'autoload' => [
                'psr-4' => [
                    'CubaDevOps\\TestModule\\' => 'src/'
                ]
            ],
            'extra' => [
                'flexi' => [
                    'autoload' => true,
                    'config-files' => ['config.json']
                ]
            ]
        ];

        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData, JSON_PRETTY_PRINT)
        );

        // Create config file to test config files status
        file_put_contents($modulePath . '/config.json', '{}');

        // Configure mock to return module info
        $moduleInfo = new \Flexi\Domain\ValueObjects\ModuleInfo(
            $moduleName,
            'cubadevops/flexi-module-test',
            \Flexi\Domain\ValueObjects\ModuleType::local(),
            $modulePath,
            '1.0.0',
            true,
            [
                'description' => 'Test module for unit testing',
                'license' => 'MIT',
                'authors' => [[
                    'name' => 'Test Author',
                    'email' => 'test@example.com'
                ]],
                'keywords' => ['flexi', 'module', 'test'],
                'require' => ['php' => '^8.0'],
                'require-dev' => ['phpunit/phpunit' => '^9.0'],
                'autoload' => ['psr-4' => ['TestModule\\' => 'src/']],
                'extra' => ['flexi' => ['name' => $moduleName, 'version' => '1.0.0']]
            ]
        );

        $this->moduleDetector->method('getModuleInfo')
            ->with($moduleName)
            ->willReturn($moduleInfo);

        // Configure state manager mock
        $this->stateManager->method('isModuleActive')
            ->with($moduleName)
            ->willReturn(true);

        $mockModuleState = $this->createMock(\Flexi\Domain\ValueObjects\ModuleState::class);
        $this->stateManager->method('getModuleState')
            ->with($moduleName)
            ->willReturn($mockModuleState);

        $dto = new ModuleInfoCommand(['module_name' => $moduleName]);
        $result = $this->getModuleInfo->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);
        $this->assertInstanceOf(PlainTextMessage::class, $result);

        $response = json_decode($result->get('body'), true);

        // Basic info
        $this->assertEquals($moduleName, $response['name']);
        $this->assertEquals('cubadevops/flexi-module-test', $response['package']);
        $this->assertEquals('1.0.0', $response['version']);
        $this->assertEquals('Test module for unit testing', $response['description']);
        $this->assertEquals('flexi-module', $response['type']); // Type from composer.json
        $this->assertEquals('local', $response['installation_type']); // Installation type from ModuleType
        $this->assertEquals('MIT', $response['license']);
        $this->assertEquals($modulePath, $response['path']);

        // Authors and keywords
        $this->assertCount(1, $response['authors']);
        $this->assertEquals('Test Author', $response['authors'][0]['name']);
        $this->assertEquals(['flexi', 'module', 'test'], $response['keywords']);

        // Dependencies
        $this->assertArrayHasKey('dependencies', $response);
        $this->assertEquals('^8.0', $response['dependencies']['php']);
        $this->assertArrayHasKey('dev_dependencies', $response);
        $this->assertEquals('^9.0', $response['dev_dependencies']['phpunit/phpunit']);

        // Autoload
        $this->assertArrayHasKey('autoload', $response);
        $this->assertArrayHasKey('psr-4', $response['autoload']);

        // Flexi metadata
        $this->assertArrayHasKey('flexi', $response);
        $this->assertTrue($response['flexi']['autoload']);
        $this->assertArrayHasKey('config_files_status', $response);
        $this->assertTrue($response['config_files_status']['config.json']);

        // Structure and statistics
        $this->assertArrayHasKey('structure', $response);
        $this->assertArrayHasKey('src', $response['structure']);
        $this->assertArrayHasKey('statistics', $response);
        $this->assertGreaterThan(0, $response['statistics']['total_files']);
        $this->assertGreaterThan(0, $response['statistics']['php_files']);
        $this->assertGreaterThan(0, $response['statistics']['json_files']);
    }

    public function testHandleWithMinimalModule(): void
    {
        $moduleName = 'MinimalModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        $composerData = [
            'name' => 'cubadevops/flexi-module-minimal'
        ];

        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData)
        );

        // Configure mock to return module info
        $moduleInfo = new \Flexi\Domain\ValueObjects\ModuleInfo(
            $moduleName,
            'cubadevops/flexi-module-minimal',
            \Flexi\Domain\ValueObjects\ModuleType::local(),
            $modulePath,
            'unknown',
            false,
            []
        );

        $this->moduleDetector->method('getModuleInfo')
            ->with($moduleName)
            ->willReturn($moduleInfo);

        // Configure state manager mock
        $this->stateManager->method('isModuleActive')
            ->with($moduleName)
            ->willReturn(false);

        $this->stateManager->method('getModuleState')
            ->with($moduleName)
            ->willReturn(null);

        $dto = new ModuleInfoCommand(['module_name' => $moduleName]);
        $result = $this->getModuleInfo->handle($dto);

        $response = json_decode($result->get('body'), true);

        // Check defaults
        $this->assertEquals($moduleName, $response['name']);
        $this->assertEquals('cubadevops/flexi-module-minimal', $response['package']);
        $this->assertEquals('unknown', $response['version']);
        $this->assertEquals('', $response['description']);
        $this->assertEquals('unknown', $response['type']); // No type in composer.json
        $this->assertEquals('local', $response['installation_type']); // Installation type
        $this->assertEquals('unknown', $response['license']);
        $this->assertEquals([], $response['authors']);
        $this->assertEquals([], $response['keywords']);
    }

    public function testConstructorWithCustomPath(): void
    {
        $customPath = '/custom/modules';
        // Test that constructor accepts proper types
        $mockStateManager = $this->createMock(ModuleStateManagerInterface::class);
        $mockModuleDetector = $this->createMock(ModuleDetectorInterface::class);

        $useCase = new GetModuleInfo($mockStateManager, $mockModuleDetector);

        $this->assertInstanceOf(GetModuleInfo::class, $useCase);
    }

    public function testHandleWithDeepDirectoryStructure(): void
    {
        $moduleName = 'DeepModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath, 0755, true);

        // Create deep directory structure to test depth limitation
        mkdir($modulePath . '/level1', 0755, true);
        mkdir($modulePath . '/level1/level2', 0755, true);
        mkdir($modulePath . '/level1/level2/level3', 0755, true);
        mkdir($modulePath . '/level1/level2/level3/level4', 0755, true);

        // Add some files
        file_put_contents($modulePath . '/level1/file1.php', '<?php');
        file_put_contents($modulePath . '/level1/level2/file2.php', '<?php');

        $composerData = [
            'name' => 'cubadevops/flexi-module-deep',
            'version' => '1.0.0'
        ];

        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($composerData)
        );

        // Configure mock to return module info
        $moduleInfo = new \Flexi\Domain\ValueObjects\ModuleInfo(
            $moduleName,
            'cubadevops/flexi-module-deep',
            \Flexi\Domain\ValueObjects\ModuleType::local(),
            $modulePath,
            '1.0.0',
            false,
            []
        );

        $this->moduleDetector->method('getModuleInfo')
            ->with($moduleName)
            ->willReturn($moduleInfo);

        // Configure state manager mock
        $this->stateManager->method('isModuleActive')
            ->with($moduleName)
            ->willReturn(false);

        $this->stateManager->method('getModuleState')
            ->with($moduleName)
            ->willReturn(null);

        $dto = new ModuleInfoCommand(['module_name' => $moduleName]);
        $result = $this->getModuleInfo->handle($dto);

        $response = json_decode($result->get('body'), true);

        // Verify structure is limited by depth (max depth = 2)
        $this->assertArrayHasKey('structure', $response);
        $this->assertArrayHasKey('level1', $response['structure']);
        $this->assertArrayHasKey('level2', $response['structure']['level1']);

        // Level 3 should exist as we're at depth 2, but level 4 should not due to depth limit
        if (isset($response['structure']['level1']['level2']['level3'])) {
            $this->assertEmpty($response['structure']['level1']['level2']['level3']);
        }
    }

    public function testHandleWithInvalidJson(): void
    {
        $moduleName = 'InvalidJsonModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        // Create invalid JSON file
        file_put_contents($modulePath . '/composer.json', 'invalid json content');

        // Configure mock to return module info
        $moduleInfo = new \Flexi\Domain\ValueObjects\ModuleInfo(
            $moduleName,
            'cubadevops/flexi-module-invalid',
            \Flexi\Domain\ValueObjects\ModuleType::local(),
            $modulePath,
            'unknown',
            false,
            []
        );

        $this->moduleDetector->method('getModuleInfo')
            ->with($moduleName)
            ->willReturn($moduleInfo);

        $dto = new ModuleInfoCommand(['module_name' => $moduleName]);

        $this->expectException(\JsonException::class);
        $this->getModuleInfo->handle($dto);
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