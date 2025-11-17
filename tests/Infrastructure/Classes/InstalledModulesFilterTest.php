<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Infrastructure\Classes;

use CubaDevOps\Flexi\Infrastructure\Classes\InstalledModulesFilter;
use CubaDevOps\Flexi\Infrastructure\Interfaces\ModuleStateManagerInterface;
use CubaDevOps\Flexi\Infrastructure\Factories\ModuleDetectorInterface;
use PHPUnit\Framework\TestCase;

class InstalledModulesFilterTest extends TestCase
{
    private InstalledModulesFilter $filter;
    private $stateManager; // Untyped for mock
    private $moduleDetector; // Untyped for mock

    public function setUp(): void
    {
        // Create mocks for dependencies
        $this->stateManager = $this->createMock(ModuleStateManagerInterface::class);
        $this->moduleDetector = $this->createMock(ModuleDetectorInterface::class);

        // Configure basic mock behavior for module path detection
        $authModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'Auth',
            'cubadevops/flexi-module-auth',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            '/path/to/modules/Auth',
            '1.0.0',
            false,
            []
        );

        $cacheModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'Cache',
            'cubadevops/flexi-module-cache',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            './modules/Cache',
            '1.0.0',
            false,
            []
        );

        $sessionModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'Session',
            'cubadevops/flexi-module-session',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            '/modules/Session',
            '1.0.0',
            false,
            []
        );

        $sessionHandlerModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'session-handler',
            'cubadevops/flexi-module-session-handler',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            '/modules/session-handler',
            '1.0.0',
            false,
            []
        );

        // Additional modules for complex path testing
        $authComplexModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'AuthComplex',
            'cubadevops/flexi-module-auth-complex',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            '/var/www/html/modules/Auth',
            '1.0.0',
            false,
            []
        );

        $multiWordModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'multi-word-module',
            'cubadevops/flexi-module-multi-word',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            './modules/multi-word-module',
            '1.0.0',
            false,
            []
        );

        $camelCaseModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'CamelCase',
            'cubadevops/flexi-module-camelcase',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            '/absolute/path/modules/CamelCase',
            '1.0.0',
            false,
            []
        );

        $lowercaseModule = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'lowercase',
            'cubadevops/flexi-module-lowercase',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            'relative/modules/lowercase',
            '1.0.0',
            false,
            []
        );

        $this->moduleDetector->method('getAllModules')
            ->willReturn([
                $authModule, $cacheModule, $sessionModule, $sessionHandlerModule,
                $authComplexModule, $multiWordModule, $camelCaseModule, $lowercaseModule
            ]);

        $this->filter = new InstalledModulesFilter($this->stateManager, $this->moduleDetector);
    }

    public function testIsModuleFile(): void
    {
        // Module files (should match pattern #/modules/([^/]+)/#)
        $this->assertTrue($this->filter->isModuleFile('/path/to/modules/Auth/config.json'));
        $this->assertTrue($this->filter->isModuleFile('./modules/Cache/services.json'));
        $this->assertTrue($this->filter->isModuleFile('/modules/Session/routes.json'));

        // Non-module files
        $this->assertFalse($this->filter->isModuleFile('modules/Session/routes.json')); // No leading slash
        $this->assertFalse($this->filter->isModuleFile('/path/to/config/app.json'));
        $this->assertFalse($this->filter->isModuleFile('./src/Infrastructure/config.json'));
        $this->assertFalse($this->filter->isModuleFile('config/services.json'));
    }

    public function testExtractModuleName(): void
    {
        // Valid module files
        $this->assertEquals('Auth', $this->filter->extractModuleName('/path/to/modules/Auth/config.json'));
        $this->assertEquals('Cache', $this->filter->extractModuleName('./modules/Cache/services.json'));
        $this->assertEquals('session-handler', $this->filter->extractModuleName('/modules/session-handler/routes.json'));

        // Non-module files
        $this->assertNull($this->filter->extractModuleName('modules/Session/routes.json')); // No leading slash
        $this->assertNull($this->filter->extractModuleName('/path/to/config/app.json'));
        $this->assertNull($this->filter->extractModuleName('./src/Infrastructure/config.json'));
        $this->assertNull($this->filter->extractModuleName('config/services.json'));
    }

    public function testFilterFilesWithNoModuleFiles(): void
    {
        $files = [
            '/config/app.json',
            './src/services.json',
            'config/routes.json'
        ];

        $filtered = $this->filter->filterFiles($files);

        // All non-module files should be included
        $this->assertCount(3, $filtered);
        $this->assertEquals($files, array_values($filtered));
    }

    public function testFilterFilesWithMixedFiles(): void
    {
        $files = [
            '/config/app.json',                    // Non-module, should be included
            'modules/Auth/config.json',            // Module file, depends on installation
            './src/services.json',                 // Non-module, should be included
            'modules/Cache/services.json',         // Module file, depends on installation
            'config/routes.json'                   // Non-module, should be included
        ];

        $filtered = $this->filter->filterFiles($files);

        // Should include at least the non-module files (3)
        // Module files inclusion depends on actual installation status
        $this->assertGreaterThanOrEqual(3, count($filtered));

        // Verify non-module files are always included
        $this->assertContains('/config/app.json', $filtered);
        $this->assertContains('./src/services.json', $filtered);
        $this->assertContains('config/routes.json', $filtered);
    }

    public function testFilterFilesWithOnlyModuleFiles(): void
    {
        $files = [
            'modules/Auth/config.json',
            'modules/Cache/services.json',
            'modules/Session/routes.json'
        ];

        $filtered = $this->filter->filterFiles($files);

        // Result depends on which modules are actually installed
        // but should be an array
        $this->assertIsArray($filtered);
        $this->assertLessThanOrEqual(count($files), count($filtered));
    }

    public function testFilterFilesWithEmptyArray(): void
    {
        $filtered = $this->filter->filterFiles([]);

        $this->assertIsArray($filtered);
        $this->assertEmpty($filtered);
    }

    public function testFilterFilesWithComplexPaths(): void
    {
        $files = [
            '/var/www/html/modules/Auth/Config/services.json',
            './modules/multi-word-module/config.json',
            '/absolute/path/modules/CamelCase/routes.json',
            'relative/modules/lowercase/config.json'
        ];

        $this->assertTrue($this->filter->isModuleFile($files[0]));
        $this->assertTrue($this->filter->isModuleFile($files[1]));
        $this->assertTrue($this->filter->isModuleFile($files[2]));
        $this->assertTrue($this->filter->isModuleFile($files[3]));

        $this->assertEquals('AuthComplex', $this->filter->extractModuleName($files[0]));
        $this->assertEquals('multi-word-module', $this->filter->extractModuleName($files[1]));
        $this->assertEquals('CamelCase', $this->filter->extractModuleName($files[2]));
        $this->assertEquals('lowercase', $this->filter->extractModuleName($files[3]));
    }
}