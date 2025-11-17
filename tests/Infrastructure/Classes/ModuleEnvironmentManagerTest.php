<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Classes;

use CubaDevOps\Flexi\Infrastructure\Classes\ModuleEnvironmentManager;
use Flexi\Contracts\Interfaces\ConfigurationInterface;
use PHPUnit\Framework\TestCase;

class ModuleEnvironmentManagerTest extends TestCase
{
    private ModuleEnvironmentManager $envManager;
    private string $tempDir;
    private string $tempEnvFile;
    private string $tempModuleDir;
    private string $tempModuleEnvFile;
    private $configurationMock;

    public function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/module_env_test_' . uniqid();
        mkdir($this->tempDir);

        $this->tempEnvFile = $this->tempDir . '/.env';
        $this->tempModuleDir = $this->tempDir . '/module';
        $this->tempModuleEnvFile = $this->tempModuleDir . '/.env';

        mkdir($this->tempModuleDir);

        // Create mock configuration
        $this->configurationMock = $this->createMock(ConfigurationInterface::class);
        $this->configurationMock->method('get')
            ->with('ROOT_DIR')
            ->willReturn($this->tempDir);

        $this->envManager = new ModuleEnvironmentManager($this->configurationMock);

        // Create initial main .env file
        file_put_contents($this->tempEnvFile, "#Application\ndebug=true\n");
    }

    public function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testHasModuleEnvFileReturnsTrueWhenFileExists(): void
    {
        file_put_contents($this->tempModuleEnvFile, "TEST_VAR=value");

        $this->assertTrue($this->envManager->hasModuleEnvFile($this->tempModuleDir));
    }

    public function testHasModuleEnvFileReturnsFalseWhenFileDoesNotExist(): void
    {
        $this->assertFalse($this->envManager->hasModuleEnvFile($this->tempModuleDir));
    }

    public function testReadModuleEnvironmentReturnsEmptyArrayWhenNoFile(): void
    {
        $result = $this->envManager->readModuleEnvironment($this->tempModuleDir, 'test-module');

        $this->assertEmpty($result);
    }

    public function testReadModuleEnvironmentParsesVariablesCorrectly(): void
    {
        $envContent = "# Test module variables\nTEST_VAR1=value1\nTEST_VAR2=\"value with spaces\"\n# Comment\nTEST_VAR3=value3";
        file_put_contents($this->tempModuleEnvFile, $envContent);

        $result = $this->envManager->readModuleEnvironment($this->tempModuleDir, 'test-module');

        $expected = [
            'TEST_VAR1' => 'value1',
            'TEST_VAR2' => 'value with spaces',
            'TEST_VAR3' => 'value3'
        ];

        $this->assertEquals($expected, $result);
    }

    public function testAddModuleEnvironmentAddsVariablesSuccessfully(): void
    {
        $envVars = [
            'MODULE_VAR1' => 'value1',
            'MODULE_VAR2' => 'value2'
        ];

        $result = $this->envManager->addModuleEnvironment('test-module', $envVars);

        $this->assertTrue($result);

        $mainEnvContent = file_get_contents($this->tempEnvFile);
        $this->assertStringContainsString('# === MODULE TEST-MODULE ENVIRONMENT VARIABLES ===', $mainEnvContent);
        $this->assertStringContainsString('MODULE_VAR1=value1', $mainEnvContent);
        $this->assertStringContainsString('MODULE_VAR2=value2', $mainEnvContent);
        $this->assertStringContainsString('# === END MODULE TEST-MODULE ENVIRONMENT VARIABLES ===', $mainEnvContent);
    }

    public function testRemoveModuleEnvironmentRemovesVariablesSuccessfully(): void
    {
        // First add variables
        $envVars = ['MODULE_VAR1' => 'value1'];
        $this->envManager->addModuleEnvironment('test-module', $envVars);

        // Then remove them
        $result = $this->envManager->removeModuleEnvironment('test-module');

        $this->assertTrue($result);

        $mainEnvContent = file_get_contents($this->tempEnvFile);
        $this->assertStringNotContainsString('# === MODULE TEST-MODULE ENVIRONMENT VARIABLES ===', $mainEnvContent);
        $this->assertStringNotContainsString('MODULE_VAR1=value1', $mainEnvContent);
    }

    public function testHasModuleEnvironmentReturnsTrueWhenVariablesExist(): void
    {
        $envVars = ['MODULE_VAR1' => 'value1'];
        $this->envManager->addModuleEnvironment('test-module', $envVars);

        $this->assertTrue($this->envManager->hasModuleEnvironment('test-module'));
    }

    public function testHasModuleEnvironmentReturnsFalseWhenVariablesDoNotExist(): void
    {
        $this->assertFalse($this->envManager->hasModuleEnvironment('test-module'));
    }

    public function testGetModuleEnvironmentReturnsCorrectVariables(): void
    {
        $envVars = [
            'MODULE_VAR1' => 'value1',
            'MODULE_VAR2' => 'value2'
        ];
        $this->envManager->addModuleEnvironment('test-module', $envVars);

        $result = $this->envManager->getModuleEnvironment('test-module');

        $this->assertEquals($envVars, $result);
    }

    public function testUpdateModuleEnvironmentPreservesUserModifications(): void
    {
        // Add initial variables
        $initialVars = [
            'MODULE_VAR1' => 'original1',
            'MODULE_VAR2' => 'original2'
        ];
        $this->envManager->addModuleEnvironment('test-module', $initialVars);

        // Simulate user modification by manually editing the .env file
        $mainEnvContent = file_get_contents($this->tempEnvFile);
        $mainEnvContent = str_replace('MODULE_VAR1=original1', 'MODULE_VAR1=user_modified', $mainEnvContent);
        file_put_contents($this->tempEnvFile, $mainEnvContent);

        // Update with new variables (including one that was modified by user)
        $newVars = [
            'MODULE_VAR1' => 'original1', // This should preserve user modification
            'MODULE_VAR2' => 'original2',
            'MODULE_VAR3' => 'new_value'  // This should be added
        ];

        $result = $this->envManager->updateModuleEnvironment('test-module', $newVars);

        $this->assertTrue($result);

        $finalVars = $this->envManager->getModuleEnvironment('test-module');

        $expected = [
            'MODULE_VAR1' => 'user_modified', // User modification preserved
            'MODULE_VAR2' => 'original2',
            'MODULE_VAR3' => 'new_value'      // New variable added
        ];

        $this->assertEquals($expected, $finalVars);
    }

    public function testGetModuleEnvFilePathReturnsCorrectPath(): void
    {
        $expected = $this->tempModuleDir . '/.env';
        $result = $this->envManager->getModuleEnvFilePath($this->tempModuleDir);

        $this->assertEquals($expected, $result);
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
}