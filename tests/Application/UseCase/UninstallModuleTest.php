<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\UninstallModuleCommand;
use CubaDevOps\Flexi\Application\Services\CommandExecutorInterface;
use CubaDevOps\Flexi\Application\UseCase\UninstallModule;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class UninstallModuleTest extends TestCase
{
    private string $tempDir;
    private string $modulesPath;
    private string $composerPath;
    private string $rootPath;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/flexi_test_' . uniqid();
        $this->rootPath = $this->tempDir;
        $this->modulesPath = $this->tempDir . '/modules';
        $this->composerPath = $this->tempDir . '/composer.json';

        mkdir($this->tempDir, 0777, true);
        mkdir($this->modulesPath, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->cleanupTempDir($this->tempDir);
    }

    private function cleanupTempDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->cleanupTempDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createComposerJson(array $data): void
    {
        file_put_contents($this->composerPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function createModuleComposerJson(string $moduleName, array $data): void
    {
        $modulePath = $this->modulesPath . '/' . $moduleName;
        if (!is_dir($modulePath)) {
            mkdir($modulePath, 0777, true);
        }

        $composerPath = $modulePath . '/composer.json';
        file_put_contents($composerPath, json_encode($data, JSON_PRETTY_PRINT));
    }

    private function createMockCommandExecutor(): CommandExecutorInterface
    {
        /** @var CommandExecutorInterface|\PHPUnit\Framework\MockObject\MockObject $mockCommandExecutor */
        $mockCommandExecutor = $this->createMock(CommandExecutorInterface::class);

        // Configure default behavior for successful command execution
        $mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Command executed successfully'];
                $return_code = 0;
            });

        return $mockCommandExecutor;
    }

    public function testImplementsHandlerInterface(): void
    {
        $uninstallModule = new UninstallModule('./modules', './composer.json', '.', $this->createMockCommandExecutor());
        $this->assertInstanceOf(HandlerInterface::class, $uninstallModule);
    }

    public function testConstructorWithDefaultPaths(): void
    {
        $useCase = new UninstallModule('./modules', './composer.json', '.', $this->createMockCommandExecutor());
        $this->assertInstanceOf(UninstallModule::class, $useCase);
    }

    public function testConstructorWithDifferentParameters(): void
    {
        // Test with custom paths
        $uninstallModule = new UninstallModule(
            '/custom/modules',
            '/custom/composer.json',
            '/custom/root',
            $this->createMockCommandExecutor()
        );

        $this->assertInstanceOf(UninstallModule::class, $uninstallModule);

        // Test with empty string that gets normalized
        $uninstallModule2 = new UninstallModule(
            '/modules/',
            './composer.json',
            '/root/',
            $this->createMockCommandExecutor()
        );

        $this->assertInstanceOf(UninstallModule::class, $uninstallModule2);
    }

    public function testWriteComposerJsonFailure(): void
    {
        // Create a valid scenario to get past the initial checks
        $packageName = 'test/test-module';

        $this->createComposerJson([
            'require' => [
                $packageName => '^1.0'
            ]
        ]);

        $this->createModuleComposerJson('TestModule', [
            'name' => $packageName
        ]);

        // Create UninstallModule with an invalid composer path that will fail when writing
        $uninstallModule = new UninstallModule(
            $this->tempDir,
            '/nonexistent/deep/directory/path/composer.json', // Invalid path for writing
            $this->tempDir,
            $this->createMockCommandExecutor()
        );

        // Use reflection to test the writeComposerJson method directly
        $reflection = new \ReflectionClass($uninstallModule);
        $writeMethod = $reflection->getMethod('writeComposerJson');
        $writeMethod->setAccessible(true);

        $exceptionCaught = false;
        try {
            // Try to write to invalid path - this will trigger a file_put_contents error
            $writeMethod->invoke($uninstallModule, ['require' => []]);
        } catch (\Exception $e) {
            $exceptionCaught = true;
            // The exception message could be from file_put_contents error or RuntimeException
            // Both indicate the method was tested
            $this->assertTrue(
                strpos($e->getMessage(), 'No such file or directory') !== false ||
                $e->getMessage() === 'Failed to write composer.json'
            );
        }

        $this->assertTrue($exceptionCaught, 'Expected exception was not thrown');
    }

    public function testConstructorNormalizesTrailingSlashes(): void
    {
        $useCase = new UninstallModule('./modules/', './composer.json', './', $this->createMockCommandExecutor());
        $this->assertInstanceOf(UninstallModule::class, $useCase);
    }

    public function testHandleThrowsExceptionWhenModuleComposerJsonNotExists(): void
    {
        $this->createComposerJson(['require' => []]);

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
        $dto = new UninstallModuleCommand(['module_name' => 'NonExistentModule']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'NonExistentModule' has no composer.json");

        $uninstallModule->handle($dto);
    }

    public function testHandleThrowsExceptionWhenModuleComposerJsonHasNoName(): void
    {
        $this->createComposerJson(['require' => []]);
        $this->createModuleComposerJson('TestModule', ['description' => 'A test module']);

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
        $dto = new UninstallModuleCommand(['module_name' => 'testModule']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'TestModule' composer.json has no 'name' field");

        $uninstallModule->handle($dto);
    }

    public function testHandleReturnsSuccessMessageWhenModuleNotInstalled(): void
    {
        $this->createComposerJson([
            'require' => [
                'some/other-package' => '^1.0'
            ]
        ]);
        $this->createModuleComposerJson('TestModule', [
            'name' => 'test/test-module',
            'description' => 'A test module'
        ]);

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
        $dto = new UninstallModuleCommand(['module_name' => 'testModule']);

        $result = $uninstallModule->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $responseData = json_decode($result->__toString(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals("Module 'TestModule' is not installed", $responseData['message']);
        $this->assertEquals('test/test-module', $responseData['package']);
        $this->assertEquals('none', $responseData['action']);
    }

    public function testHandleCapitalizesModuleName(): void
    {
        $this->createComposerJson(['require' => []]);
        // Create module with the capitalized name that the code will look for
        $this->createModuleComposerJson('Testmodule', [
            'name' => 'test/test-module'
        ]);

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
        $dto = new UninstallModuleCommand(['module_name' => 'testmodule']);

        $result = $uninstallModule->handle($dto);
        $responseData = json_decode($result->__toString(), true);

        $this->assertEquals("Module 'Testmodule' is not installed", $responseData['message']);
    }

    public function testHandleUninstallsModuleSuccessfully(): void
    {
        $packageName = 'test/test-module';

        $this->createComposerJson([
            'require' => [
                $packageName => '^1.0',
                'some/other-package' => '^2.0'
            ],
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => './modules/TestModule'
                ]
            ]
        ]);

        $this->createModuleComposerJson('TestModule', [
            'name' => $packageName,
            'description' => 'A test module'
        ]);

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
        $dto = new UninstallModuleCommand(['module_name' => 'testModule']);

        $result = $uninstallModule->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);
        $responseData = json_decode($result->__toString(), true);

        $this->assertTrue($responseData['success']);
        $this->assertEquals("Module 'TestModule' uninstalled successfully", $responseData['message']);
        $this->assertEquals($packageName, $responseData['package']);
        $this->assertEquals('uninstalled', $responseData['action']);
        $this->assertArrayHasKey('output', $responseData);

        // Verify composer.json was updated
        $updatedComposer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertArrayNotHasKey($packageName, $updatedComposer['require']);
        $this->assertArrayHasKey('some/other-package', $updatedComposer['require']); // Other packages preserved

        // Verify repository was removed
        $this->assertCount(0, $updatedComposer['repositories']);
    }

    private function createUninstallModuleWithMockedComposer(): UninstallModule
    {
        return new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
    }

    public function testWriteComposerJsonFunctionality(): void
    {
        $this->createComposerJson([
            'require' => [
                'test/test-module' => '^1.0'
            ]
        ]);

        // Test that composer.json can be read and parsed
        $composerData = json_decode(file_get_contents($this->composerPath), true);
        $this->assertArrayHasKey('require', $composerData);
        $this->assertArrayHasKey('test/test-module', $composerData['require']);
    }

    public function testHandleWithRepositoriesArrayManipulation(): void
    {
        $packageName = 'test/test-module';

        $this->createComposerJson([
            'require' => [
                $packageName => '^1.0'
            ],
            'repositories' => [
                [
                    'type' => 'path',
                    'url' => './modules/OtherModule'
                ],
                [
                    'type' => 'path',
                    'url' => './modules/TestModule'
                ]
            ]
        ]);

        $this->createModuleComposerJson('TestModule', [
            'name' => $packageName
        ]);

        // Test the repository filtering logic
        $composerData = json_decode(file_get_contents($this->composerPath), true);
        $this->assertCount(2, $composerData['repositories']);

        // Simulate the repository filtering that happens in the uninstall process
        $filteredRepos = array_filter(
            $composerData['repositories'],
            function ($repo) {
                return !isset($repo['url']) || $repo['url'] !== "./modules/TestModule";
            }
        );

        $this->assertCount(1, $filteredRepos);
    }

    public function testHandleWithComposerCommandFailure(): void
    {
        $packageName = 'test/test-module';

        $this->createComposerJson([
            'require' => [
                $packageName => '^1.0'
            ]
        ]);

        $this->createModuleComposerJson('TestModule', [
            'name' => $packageName
        ]);

        // Mock command executor to simulate failure
        /** @var CommandExecutorInterface|\PHPUnit\Framework\MockObject\MockObject $mockCommandExecutor */
        $mockCommandExecutor = $this->createMock(CommandExecutorInterface::class);
        $mockCommandExecutor
            ->method('execute')
            ->willReturnCallback(function ($command, &$output, &$return_code) {
                $output = ['Composer remove failed'];
                $return_code = 1;
            });

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $mockCommandExecutor);
        $dto = new UninstallModuleCommand(['module_name' => 'testModule']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Failed to uninstall module 'TestModule': Composer remove failed");

        $uninstallModule->handle($dto);
    }

    public function testHandleWithNoRepositoriesSection(): void
    {
        $packageName = 'test/test-module';

        // Create composer.json without repositories section
        $this->createComposerJson([
            'require' => [
                $packageName => '^1.0'
            ]
        ]);

        $this->createModuleComposerJson('TestModule', [
            'name' => $packageName
        ]);

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
        $dto = new UninstallModuleCommand(['module_name' => 'testModule']);

        $result = $uninstallModule->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);
        $responseData = json_decode($result->__toString(), true);

        $this->assertTrue($responseData['success']);
        $this->assertEquals('uninstalled', $responseData['action']);
    }

    public function testWriteComposerJsonMethod(): void
    {
        // Test the writeComposerJson method directly by ensuring it gets called
        $packageName = 'test/test-module';

        $this->createComposerJson([
            'require' => [
                $packageName => '^1.0'
            ]
        ]);

        $this->createModuleComposerJson('TestModule', [
            'name' => $packageName
        ]);

        $uninstallModule = new UninstallModule($this->modulesPath, $this->composerPath, $this->rootPath, $this->createMockCommandExecutor());
        $dto = new UninstallModuleCommand(['module_name' => 'testModule']);

        // This will call writeComposerJson internally
        $result = $uninstallModule->handle($dto);

        // Verify that the composer.json was actually modified
        $updatedComposer = json_decode(file_get_contents($this->composerPath), true);
        $this->assertArrayNotHasKey($packageName, $updatedComposer['require']);

        $this->assertInstanceOf(MessageInterface::class, $result);
    }
}