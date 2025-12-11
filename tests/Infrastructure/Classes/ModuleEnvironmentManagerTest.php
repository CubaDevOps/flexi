<?php

declare(strict_types=1);

namespace Flexi\Tests\Infrastructure\Classes;

use Flexi\Infrastructure\Classes\ModuleEnvironmentManager;
use Flexi\Test\TestData\TestDoubles\Configuration\ArrayConfiguration;
use Flexi\Test\TestData\TestDoubles\Modules\ModuleEnvironmentManagerAlwaysHas;
use Flexi\Test\TestData\TestDoubles\Modules\ModuleEnvironmentManagerRemovalFails;
use PHPUnit\Framework\TestCase;

class ModuleEnvironmentManagerTest extends TestCase
{
    private string $rootDir;
    private string $mainEnvPath;
    private ModuleEnvironmentManager $manager;

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir() . '/flexi-module-env-' . uniqid('', true);
        $this->createDirectory($this->rootDir);

        $this->mainEnvPath = $this->rootDir . '/.env';
        file_put_contents($this->mainEnvPath, "BASE_ENV=1\n");

        $configuration = new ArrayConfiguration(['ROOT_DIR' => $this->rootDir]);
        $this->manager = new ModuleEnvironmentManager($configuration);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->rootDir ?? '');
        parent::tearDown();
    }

    public function testReadModuleEnvironmentParsesVariables(): void
    {
        $modulePath = $this->createModule('Blog', <<<ENV
        # Comment line
        API_KEY=123
        GREETING="Hello World"
        EMPTY_QUOTED=""
        EMPTY_RAW=
        ENV
                );

        $env = $this->manager->readModuleEnvironment($modulePath, 'Blog');

        $this->assertSame(
            [
                'API_KEY' => '123',
                'GREETING' => 'Hello World',
                'EMPTY_QUOTED' => '',
                'EMPTY_RAW' => '',
            ],
            $env
        );
    }

    public function testReadModuleEnvironmentReturnsEmptyWhenFileMissing(): void
    {
        $modulePath = $this->createModule('Missing');

        $this->assertSame([], $this->manager->readModuleEnvironment($modulePath, 'Missing'));
    }

    public function testAddModuleEnvironmentAppendsBlockAndQuotesValues(): void
    {
        $result = $this->manager->addModuleEnvironment('blog', [
            'API_KEY' => '123',
            'GREETING' => 'hello world',
            'HASHED' => 'value#1',
        ]);

        $this->assertTrue($result);
        $this->assertTrue($this->manager->hasModuleEnvironment('blog'));

        $content = file_get_contents($this->mainEnvPath);
        $this->assertIsString($content);
        $this->assertStringContainsString('BASE_ENV=1', $content);
        $this->assertStringContainsString('# === MODULE BLOG ENVIRONMENT VARIABLES ===', $content);
        $this->assertStringContainsString('GREETING="hello world"', $content);
        $this->assertStringContainsString('HASHED="value#1"', $content);

        $env = $this->manager->getModuleEnvironment('blog');
        $this->assertSame([
            'API_KEY' => '123',
            'GREETING' => 'hello world',
            'HASHED' => 'value#1',
        ], $env);
    }

    public function testAddModuleEnvironmentSkipsWhenPayloadEmpty(): void
    {
        $before = file_get_contents($this->mainEnvPath);

        $this->assertTrue($this->manager->addModuleEnvironment('blog', []));
        $this->assertSame($before, file_get_contents($this->mainEnvPath));
    }

    public function testRemoveModuleEnvironmentClearsBlock(): void
    {
        $this->manager->addModuleEnvironment('blog', ['API_KEY' => '123']);
        $this->assertTrue($this->manager->hasModuleEnvironment('blog'));

        $this->assertTrue($this->manager->removeModuleEnvironment('blog'));
        $this->assertFalse($this->manager->hasModuleEnvironment('blog'));

        $content = file_get_contents($this->mainEnvPath);
        $this->assertIsString($content);
        $this->assertStringNotContainsString('MODULE BLOG ENVIRONMENT VARIABLES', $content);
        $this->assertStringContainsString('BASE_ENV=1', $content);
    }

    public function testRemoveModuleEnvironmentIsNoOpWhenMissing(): void
    {
        $before = file_get_contents($this->mainEnvPath);

        $this->assertTrue($this->manager->removeModuleEnvironment('blog'));
        $this->assertSame($before, file_get_contents($this->mainEnvPath));
    }

    public function testUpdateModuleEnvironmentPreservesExistingValues(): void
    {
        $this->manager->addModuleEnvironment('blog', [
            'API_KEY' => '123',
            'FEATURE_FLAG' => 'true',
        ]);

        $original = file_get_contents($this->mainEnvPath);
        $this->assertIsString($original);
        $modified = str_replace('API_KEY=123', 'API_KEY=custom', $original);
        file_put_contents($this->mainEnvPath, $modified);

        $this->assertTrue($this->manager->updateModuleEnvironment('blog', [
            'API_KEY' => '999',
            'NEW_VAR' => 'fresh',
        ]));

        $env = $this->manager->getModuleEnvironment('blog');
        $this->assertSame([
            'API_KEY' => 'custom',
            'NEW_VAR' => 'fresh',
            'FEATURE_FLAG' => 'true',
        ], $env);
    }

    public function testUpdateModuleEnvironmentAddsBlockWhenMissing(): void
    {
        $this->assertFalse($this->manager->hasModuleEnvironment('blog'));

        $this->assertTrue($this->manager->updateModuleEnvironment('blog', ['API_KEY' => '123']));
        $this->assertSame(['API_KEY' => '123'], $this->manager->getModuleEnvironment('blog'));
    }

    public function testGetModuleEnvFilePathAndHasModuleEnvFile(): void
    {
        $modulePath = $this->createModule('Blog', "API_KEY=123\n");

        $this->assertTrue($this->manager->hasModuleEnvFile($modulePath));
        $this->assertFalse($this->manager->hasModuleEnvFile($this->rootDir . '/Unknown'));

        $expectedPath = $modulePath . '/.env';
        $this->assertSame($expectedPath, $this->manager->getModuleEnvFilePath($modulePath . '/'));
    }

    public function testReadModuleEnvironmentReturnsEmptyWhenDirectoryDoesNotExist(): void
    {
        // Test with a non-existent module path
        $nonExistentPath = $this->rootDir . '/non-existent-module';

        $env = $this->manager->readModuleEnvironment($nonExistentPath, 'NonExistent');

        // Should return empty array when directory doesn't exist
        $this->assertSame([], $env);
    }

    public function testReadModuleEnvironmentHandlesMalformedContent(): void
    {
        // Create a module with malformed .env content
        $modulePath = $this->createModule('Malformed', <<<ENV
        # Valid comment
        VALID_KEY=value

        # Lines without equals sign (invalid)
        JUST_A_LINE
        ANOTHER_LINE_NO_EQUALS

        # Empty key (should be skipped)
        =empty_key_value

        # Valid keys after invalid ones
        ANOTHER_VALID=test
        ENV
                );

        $env = $this->manager->readModuleEnvironment($modulePath, 'Malformed');

        // Should parse only valid key=value pairs
        $this->assertArrayHasKey('VALID_KEY', $env);
        $this->assertArrayHasKey('ANOTHER_VALID', $env);
        $this->assertSame('value', $env['VALID_KEY']);
        $this->assertSame('test', $env['ANOTHER_VALID']);

        // Should not include invalid lines
        $this->assertArrayNotHasKey('JUST_A_LINE', $env);
        $this->assertArrayNotHasKey('ANOTHER_LINE_NO_EQUALS', $env);
    }

    public function testAddModuleEnvironmentFailsWhenUnableToWrite(): void
    {
        $lockedDir = $this->rootDir . '/locked-env-path';
        $this->createDirectory($lockedDir);

        $this->setMainEnvPath($lockedDir);

        $result = $this->manager->addModuleEnvironment('blog', ['API_KEY' => '123']);

        $this->assertFalse($result);
        $this->assertFalse($this->manager->hasModuleEnvironment('blog'));
    }

    public function testHasModuleEnvironmentReturnsFalseWhenEnvFileMissing(): void
    {
        $missingPath = $this->rootDir . '/missing/.env';
        $this->setMainEnvPath($missingPath);

        $this->assertFalse($this->manager->hasModuleEnvironment('blog'));
    }

    public function testRemoveModuleEnvironmentReturnsFalseWhenFileCannotBeRead(): void
    {
        $configuration = new ArrayConfiguration(['ROOT_DIR' => $this->rootDir]);

        $manager = new ModuleEnvironmentManagerAlwaysHas($configuration);

        $this->setMainEnvPathFor($manager, $this->rootDir . '/missing/.env');

        $this->assertFalse($manager->removeModuleEnvironment('blog'));
    }

    public function testUpdateModuleEnvironmentReturnsFalseWhenRemovalFails(): void
    {
        $configuration = new ArrayConfiguration(['ROOT_DIR' => $this->rootDir]);

        $manager = new ModuleEnvironmentManagerRemovalFails($configuration);

        $this->assertFalse($manager->updateModuleEnvironment('blog', ['NEW_KEY' => 'value']));
        $this->assertTrue($manager->removeCalled);
    }

    private function createModule(string $name, ?string $envContent = null): string
    {
        $modulePath = $this->rootDir . '/modules/' . $name;
        $this->createDirectory($modulePath);

        if ($envContent !== null) {
            file_put_contents($modulePath . '/.env', $envContent);
        }

        return $modulePath;
    }

    private function createDirectory(string $path): void
    {
        if ('' === $path || is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new \RuntimeException(sprintf('Could not create directory %s', $path));
        }
    }

    private function removeDirectory(string $path): void
    {
        if ('' === $path || !is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
                continue;
            }

            unlink($fileInfo->getPathname());
        }

        rmdir($path);
    }

    private function setMainEnvPath(string $path): void
    {
        $this->setMainEnvPathFor($this->manager, $path);
    }

    private function setMainEnvPathFor(ModuleEnvironmentManager $manager, string $path): void
    {
        $reflection = new \ReflectionClass(ModuleEnvironmentManager::class);
        $property = $reflection->getProperty('mainEnvPath');
        $property->setAccessible(true);
        $property->setValue($manager, $path);
    }

    public function testBuildModuleEnvironmentBlockWithSpecialCharacters(): void
    {
        $this->manager->addModuleEnvironment('test', [
            'SIMPLE' => 'value',
            'WITH_SPACE' => 'hello world',
            'WITH_HASH' => 'value#123',
            'WITH_QUOTES' => 'say "hello"',
            'MIXED' => 'test #value with spaces',
        ]);

        $content = file_get_contents($this->mainEnvPath);
        $this->assertIsString($content);

        // Values with spaces should be quoted
        $this->assertStringContainsString('WITH_SPACE="hello world"', $content);
        // Values with hash should be quoted
        $this->assertStringContainsString('WITH_HASH="value#123"', $content);
        // Values with quotes should be escaped and quoted
        $this->assertStringContainsString('WITH_QUOTES=', $content);
        // Mixed special characters
        $this->assertStringContainsString('MIXED="test #value with spaces"', $content);
    }

    public function testGetModuleEnvironmentReturnsEmptyWhenModuleNotRegistered(): void
    {
        // Try to get environment for a module that was never added
        $result = $this->manager->getModuleEnvironment('nonexistent');

        // Should return empty array when module is not registered
        $this->assertSame([], $result);

        // Also verify hasModuleEnvironment returns false
        $this->assertFalse($this->manager->hasModuleEnvironment('nonexistent'));
    }

    public function testRemoveModuleEnvironmentCleansUpMultipleNewlines(): void
    {
        $this->manager->addModuleEnvironment('blog', ['KEY1' => 'value1']);
        $this->manager->addModuleEnvironment('shop', ['KEY2' => 'value2']);

        // Remove first module
        $this->assertTrue($this->manager->removeModuleEnvironment('blog'));

        $content = file_get_contents($this->mainEnvPath);
        $this->assertIsString($content);

        // Should not have more than 2 consecutive newlines
        $this->assertStringNotContainsString("\n\n\n", $content);
    }

    public function testUpdateModuleEnvironmentReturnsTrue(): void
    {
        // Test that update returns true on success
        $this->manager->addModuleEnvironment('test', ['OLD_KEY' => 'old_value']);

        $result = $this->manager->updateModuleEnvironment('test', ['NEW_KEY' => 'new_value']);

        $this->assertTrue($result);

        $env = $this->manager->getModuleEnvironment('test');
        $this->assertArrayHasKey('OLD_KEY', $env);
        $this->assertArrayHasKey('NEW_KEY', $env);
    }

    public function testReadModuleEnvironmentWithComplexValues(): void
    {
        $modulePath = $this->createModule('Complex', <<<ENV
        # Complex test cases
        SIMPLE=value
        WITH_EQUALS=key=value=test
        SINGLE_QUOTED='single quotes'
        DOUBLE_QUOTED="double quotes"
        EMPTY_LINE_BEFORE=test

        # Another comment
        AFTER_EMPTY=value
        ENV
                );

        $env = $this->manager->readModuleEnvironment($modulePath, 'Complex');

        $this->assertArrayHasKey('SIMPLE', $env);
        $this->assertArrayHasKey('WITH_EQUALS', $env);
        $this->assertSame('key=value=test', $env['WITH_EQUALS']);
        $this->assertSame('single quotes', $env['SINGLE_QUOTED']);
        $this->assertSame('double quotes', $env['DOUBLE_QUOTED']);
    }

    public function testAddModuleEnvironmentUpdatesExistingModule(): void
    {
        // Add initial environment
        $this->manager->addModuleEnvironment('test', ['KEY1' => 'value1']);

        // Try to add again - should update instead
        $result = $this->manager->addModuleEnvironment('test', ['KEY2' => 'value2']);

        $this->assertTrue($result);

        $env = $this->manager->getModuleEnvironment('test');
        $this->assertArrayHasKey('KEY1', $env);
        $this->assertArrayHasKey('KEY2', $env);
    }

    public function testRemoveModuleEnvironmentHandlesTrailingNewline(): void
    {
        // Add module with content that has trailing newlines
        file_put_contents($this->mainEnvPath, "BASE_VAR=1\n\n");
        $this->manager->addModuleEnvironment('test', ['KEY' => 'value']);

        $beforeRemove = file_get_contents($this->mainEnvPath);
        $this->assertIsString($beforeRemove);

        $this->assertTrue($this->manager->removeModuleEnvironment('test'));

        $afterRemove = file_get_contents($this->mainEnvPath);
        $this->assertIsString($afterRemove);
        $this->assertStringContainsString('BASE_VAR=1', $afterRemove);
        $this->assertStringNotContainsString('MODULE TEST', $afterRemove);
    }

    public function testParseEnvironmentVariablesHandlesEdgeCases(): void
    {
        $this->manager->addModuleEnvironment('edge', [
            'NO_VALUE' => '',
            'KEY_ONLY' => 'value',
        ]);

        $env = $this->manager->getModuleEnvironment('edge');

        $this->assertArrayHasKey('NO_VALUE', $env);
        $this->assertSame('', $env['NO_VALUE']);
        $this->assertArrayHasKey('KEY_ONLY', $env);
    }
}