<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\ModuleInfoCommand;
use CubaDevOps\Flexi\Application\UseCase\GetModuleInfo;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class GetModuleInfoTest extends TestCase
{
    private GetModuleInfo $getModuleInfo;
    private string $tempModulesPath;

    public function setUp(): void
    {
        $this->tempModulesPath = sys_get_temp_dir() . '/test_modules_info_' . uniqid();
        mkdir($this->tempModulesPath);
        $this->getModuleInfo = new GetModuleInfo($this->tempModulesPath);
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

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'NonExistentModule' not found");

        $this->getModuleInfo->handle($dto);
    }

    public function testHandleThrowsExceptionWhenComposerJsonNotFound(): void
    {
        $moduleName = 'BrokenModule';
        $modulePath = $this->tempModulesPath . '/' . $moduleName;
        mkdir($modulePath);

        $dto = new ModuleInfoCommand(['module_name' => $moduleName]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'BrokenModule' has no composer.json");

        $this->getModuleInfo->handle($dto);
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
        $this->assertEquals('flexi-module', $response['type']);
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

        $dto = new ModuleInfoCommand(['module_name' => $moduleName]);
        $result = $this->getModuleInfo->handle($dto);

        $response = json_decode($result->get('body'), true);

        // Check defaults
        $this->assertEquals($moduleName, $response['name']);
        $this->assertEquals('cubadevops/flexi-module-minimal', $response['package']);
        $this->assertEquals('unknown', $response['version']);
        $this->assertEquals('', $response['description']);
        $this->assertEquals('unknown', $response['type']);
        $this->assertEquals('unknown', $response['license']);
        $this->assertEquals([], $response['authors']);
        $this->assertEquals([], $response['keywords']);
    }

    public function testConstructorWithCustomPath(): void
    {
        $customPath = '/custom/modules';
        $useCase = new GetModuleInfo($customPath);

        $this->assertInstanceOf(GetModuleInfo::class, $useCase);
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