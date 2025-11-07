<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\SyncModulesCommand;
use CubaDevOps\Flexi\Application\Services\CommandExecutorInterface;
use CubaDevOps\Flexi\Application\UseCase\SyncModules;
use Flexi\Contracts\Classes\PlainTextMessage;
use Flexi\Contracts\Interfaces\HandlerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SyncModulesTest extends TestCase
{
    private $mockCommandExecutor;
    private string $tempDir;
    private string $modulesPath;
    private string $composerJsonPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockCommandExecutor = $this->createMock(CommandExecutorInterface::class);

        // Create temporary directory structure for testing
        $this->tempDir = sys_get_temp_dir() . '/sync_modules_test_' . uniqid();
        $this->modulesPath = $this->tempDir . '/modules';
        $this->composerJsonPath = $this->tempDir . '/composer.json';

        mkdir($this->tempDir, 0777, true);
        mkdir($this->modulesPath, 0777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Clean up temporary directories
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testImplementsHandlerInterface(): void
    {
        $syncModules = new SyncModules('./modules', './composer.json', '.', $this->mockCommandExecutor);
        $this->assertInstanceOf(HandlerInterface::class, $syncModules);
    }

    public function testConstructorNormalizesPathsCorrectly(): void
    {
        // Test that trailing slashes are removed from paths
        $syncModules = new SyncModules('./modules/', './composer.json', './', $this->mockCommandExecutor);
        $this->assertInstanceOf(SyncModules::class, $syncModules);
    }

    public function testHandleWithNoModulesDirectory(): void
    {
        // Remove modules directory to test non-existent directory scenario
        rmdir($this->modulesPath);

        $initialComposerData = [
            'name' => 'test/project',
            'require' => [],
            'repositories' => []
        ];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData, JSON_PRETTY_PRINT));

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        $this->assertEquals(0, $resultData['discovered']);
        $this->assertEquals(0, $resultData['added']);
        $this->assertEquals(0, $resultData['removed']);
        $this->assertFalse($resultData['composer_update']['executed']);
    }

    public function testHandleWithEmptyComposerJson(): void
    {
        // Create a basic composer.json without repositories or require sections
        $initialComposerData = ['name' => 'test/project'];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        // Create a test module
        $this->createTestModule('test-module', 'cubadevops/flexi-module-test');

        $this->mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Update completed successfully'];
                $return_code = 0;
            });

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        $this->assertEquals(1, $resultData['discovered']);
        $this->assertEquals(1, $resultData['added']);
        $this->assertEquals(0, $resultData['removed']);
        $this->assertTrue($resultData['composer_update']['executed']);
        $this->assertTrue($resultData['composer_update']['success']);
    }

    public function testHandleWithExistingRepositoriesAndPackages(): void
    {
        // Create composer.json with existing repositories and packages
        $initialComposerData = [
            'name' => 'test/project',
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => './modules/existing-module',
                    'options' => ['symlink' => true]
                ]
            ],
            'require' => [
                'cubadevops/flexi-module-existing' => '@dev'
            ]
        ];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        // Create the same module that already exists in composer.json
        $this->createTestModule('existing-module', 'cubadevops/flexi-module-existing');

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        $this->assertEquals(1, $resultData['discovered']);
        $this->assertEquals(0, $resultData['added']);
        $this->assertEquals(1, $resultData['updated']);
        $this->assertEquals(0, $resultData['removed']);
        $this->assertFalse($resultData['composer_update']['executed']);
        $this->assertEquals('No changes detected', $resultData['composer_update']['reason']);
    }

    public function testHandleRemovesObsoletePackages(): void
    {
        // Create composer.json with packages that don't exist in modules directory
        $initialComposerData = [
            'name' => 'test/project',
            'repositories' => [],
            'require' => [
                'cubadevops/flexi-module-obsolete' => '@dev',
                'some/other-package' => '^1.0'  // This should not be removed
            ]
        ];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        $this->mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Update completed successfully'];
                $return_code = 0;
            });

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        $this->assertEquals(0, $resultData['discovered']);
        $this->assertEquals(0, $resultData['added']);
        $this->assertEquals(1, $resultData['removed']);
        $this->assertTrue($resultData['composer_update']['executed']);

        // Verify obsolete package was removed but other package remains
        $updatedComposer = json_decode(file_get_contents($this->composerJsonPath), true);
        $this->assertArrayNotHasKey('cubadevops/flexi-module-obsolete', $updatedComposer['require']);
        $this->assertArrayHasKey('some/other-package', $updatedComposer['require']);
    }

    public function testHandleWithInvalidModuleComposerJson(): void
    {
        // Create a module with invalid JSON
        $moduleDir = $this->modulesPath . '/invalid-module';
        mkdir($moduleDir);
        file_put_contents($moduleDir . '/composer.json', '{invalid json}');

        $initialComposerData = ['name' => 'test/project'];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        // Invalid modules should be ignored
        $this->assertEquals(0, $resultData['discovered']);
    }

    public function testHandleWithModuleWithoutName(): void
    {
        // Create a module with composer.json that doesn't have a name field
        $moduleDir = $this->modulesPath . '/no-name-module';
        mkdir($moduleDir);
        file_put_contents($moduleDir . '/composer.json', json_encode(['description' => 'Module without name']));

        $initialComposerData = ['name' => 'test/project'];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        // Modules without name should be ignored
        $this->assertEquals(0, $resultData['discovered']);
    }

    public function testHandleWithComposerUpdateFailure(): void
    {
        // Create a test module
        $this->createTestModule('test-module', 'cubadevops/flexi-module-test');

        $initialComposerData = ['name' => 'test/project'];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        $this->mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Composer update failed'];
                $return_code = 1;
            });

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        $this->assertTrue($resultData['composer_update']['executed']);
        $this->assertFalse($resultData['composer_update']['success']);
        $this->assertEquals(['Composer update failed'], $resultData['composer_update']['output']);
    }

    public function testFormatComposerJson(): void
    {
        $testData = [
            'name' => 'test/project',
            'require' => [
                'cubadevops/flexi-module-test' => '@dev'
            ]
        ];

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($syncModules);
        $method = $reflection->getMethod('formatComposerJson');
        $method->setAccessible(true);

        $result = $method->invoke($syncModules, $testData);

        $this->assertJson($result);
        $this->assertStringContainsString('"name": "test/project"', $result);
        $this->assertStringContainsString('"cubadevops/flexi-module-test": "@dev"', $result);
    }

    public function testWriteJsonToFileThrowsExceptionOnFailure(): void
    {
        // Create a SyncModules instance with a non-writable path
        $readOnlyPath = '/nonexistent/path/composer.json';

        $syncModules = new SyncModules($this->modulesPath, $readOnlyPath, $this->tempDir, $this->mockCommandExecutor);

        // Use reflection to access protected method
        $reflection = new \ReflectionClass($syncModules);
        $method = $reflection->getMethod('writeJsonToFile');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write composer.json:');

        $method->invoke($syncModules, $readOnlyPath, '{"test": "data"}');
    }

    public function testHandleWithJsonExceptionInComposerJson(): void
    {
        // Create invalid JSON in composer.json
        file_put_contents($this->composerJsonPath, '{invalid json}');

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $this->expectException(\JsonException::class);
        $syncModules->handle($dto);
    }

    public function testHandleWithComplexScenario(): void
    {
        // Create multiple modules
        $this->createTestModule('module-one', 'cubadevops/flexi-module-one');
        $this->createTestModule('module-two', 'cubadevops/flexi-module-two', '1.0.0');

        // Create composer.json with mixed state
        $initialComposerData = [
            'name' => 'test/project',
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => './modules/module-one',
                    'options' => ['symlink' => true]
                ],
                [
                    'type' => 'composer',
                    'url' => 'https://packagist.org'
                ]
            ],
            'require' => [
                'cubadevops/flexi-module-one' => '@dev',
                'cubadevops/flexi-module-obsolete' => '@dev',
                'symfony/console' => '^5.0'
            ]
        ];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        $this->mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Update completed successfully'];
                $return_code = 0;
            });

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        $this->assertEquals(2, $resultData['discovered']);
        $this->assertEquals(1, $resultData['added']);      // module-two added
        $this->assertEquals(1, $resultData['updated']);    // module-one already existed
        $this->assertEquals(1, $resultData['removed']);    // module-obsolete removed
        $this->assertTrue($resultData['composer_update']['executed']);

        // Verify the final composer.json structure
        $finalComposer = json_decode(file_get_contents($this->composerJsonPath), true);

        // Should have module repositories
        $moduleRepos = array_filter($finalComposer['repositories'], function($repo) {
            return isset($repo['url']) && str_starts_with($repo['url'], './modules/');
        });
        $this->assertCount(2, $moduleRepos);

        // Should keep non-module repository
        $otherRepos = array_filter($finalComposer['repositories'], function($repo) {
            return !isset($repo['url']) || !str_starts_with($repo['url'], './modules/');
        });
        $this->assertCount(1, $otherRepos);

        // Should have correct packages
        $this->assertArrayHasKey('cubadevops/flexi-module-one', $finalComposer['require']);
        $this->assertArrayHasKey('cubadevops/flexi-module-two', $finalComposer['require']);
        $this->assertArrayNotHasKey('cubadevops/flexi-module-obsolete', $finalComposer['require']);
        $this->assertArrayHasKey('symfony/console', $finalComposer['require']); // Non-flexi package preserved
    }

    public function testDiscoverModulesWithNonDirectoryFile(): void
    {
        // Create a file in modules directory (should be ignored)
        file_put_contents($this->modulesPath . '/some-file.txt', 'This is not a module');

        // Create one valid module
        $this->createTestModule('valid-module', 'cubadevops/flexi-module-valid');

        $initialComposerData = ['name' => 'test/project'];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        $this->mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Update completed successfully'];
                $return_code = 0;
            });

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        // Only the valid module should be discovered (file should be ignored)
        $this->assertEquals(1, $resultData['discovered']);
        $this->assertEquals(1, $resultData['added']);
    }

    public function testDiscoverModulesWithDirectoryButNoComposerJson(): void
    {
        // Create a directory without composer.json
        mkdir($this->modulesPath . '/empty-dir');

        // Create a directory with composer.json but no name
        $invalidDir = $this->modulesPath . '/invalid-dir';
        mkdir($invalidDir);
        file_put_contents($invalidDir . '/composer.json', json_encode([]));

        $initialComposerData = ['name' => 'test/project'];
        file_put_contents($this->composerJsonPath, json_encode($initialComposerData));

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $result = $syncModules->handle($dto);

        $this->assertInstanceOf(PlainTextMessage::class, $result);
        $resultData = json_decode($result->get('body'), true);

        // No valid modules should be discovered
        $this->assertEquals(0, $resultData['discovered']);
        $this->assertEquals(0, $resultData['added']);
    }

    public function testWriteComposerJsonPreservesFormatting(): void
    {
        // Test that writeComposerJson creates properly formatted JSON
        $initialData = [
            'name' => 'test/project',
            'repositories' => [],
            'require' => []
        ];
        file_put_contents($this->composerJsonPath, json_encode($initialData));

        $this->createTestModule('test-module', 'cubadevops/flexi-module-test');

        $this->mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Success'];
                $return_code = 0;
            });

        $syncModules = new SyncModules($this->modulesPath, $this->composerJsonPath, $this->tempDir, $this->mockCommandExecutor);
        $dto = new SyncModulesCommand();

        $syncModules->handle($dto);

        // Check that the resulting composer.json is properly formatted
        $content = file_get_contents($this->composerJsonPath);
        $this->assertJson($content);

        // Should be pretty-printed
        $this->assertStringContainsString('    ', $content); // Contains indentation

        // Should end with newline
        $this->assertStringEndsWith("\n", $content);
    }

    private function createTestModule(string $moduleName, string $packageName, string $version = '@dev'): void
    {
        $moduleDir = $this->modulesPath . '/' . $moduleName;
        mkdir($moduleDir);

        $moduleComposer = [
            'name' => $packageName,
            'version' => $version,
            'type' => 'library'
        ];

        file_put_contents($moduleDir . '/composer.json', json_encode($moduleComposer, JSON_PRETTY_PRINT));
    }
}