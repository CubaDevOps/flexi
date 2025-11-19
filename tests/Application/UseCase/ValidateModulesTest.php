<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\ValidateModulesCommand;
use CubaDevOps\Flexi\Application\UseCase\ValidateModules;
use CubaDevOps\Flexi\Domain\Interfaces\ModuleStateManagerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\ModuleDetectorInterface;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;

class ValidateModulesTest extends TestCase
{
    private string $tempDir;
    private string $tempModulesPath;
    private $stateManager; // Untyped for mock
    private $moduleDetector; // Untyped for mock

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir();
        $this->tempModulesPath = $this->tempDir . '/test_validate_modules_' . uniqid();

        mkdir($this->tempModulesPath, 0755, true);

        // Create mocks for dependencies
        $this->stateManager = $this->createMock(ModuleStateManagerInterface::class);
        $this->moduleDetector = $this->createMock(ModuleDetectorInterface::class);
    }

    /**
     * Helper method to create ValidateModules instance with mocks
     */
    private function createValidateModules(): ValidateModules
    {
        return new ValidateModules($this->stateManager, $this->moduleDetector);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->cleanupTempDirectories();
    }

    private function cleanupTempDirectories(): void
    {
        if (is_dir($this->tempModulesPath)) {
            $this->removeDirectory($this->tempModulesPath);
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
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    private function createValidModule(string $moduleName): void
    {
        $moduleDir = $this->tempModulesPath . '/' . $moduleName;
        mkdir($moduleDir, 0755, true);

        // Create recommended directories
        mkdir($moduleDir . '/Domain', 0755, true);
        mkdir($moduleDir . '/Infrastructure', 0755, true);
        mkdir($moduleDir . '/Config', 0755, true);

        // Create valid composer.json
        $composerData = [
            'name' => "cubadevops/flexi-module-{$moduleName}",
            'version' => '1.0.0',
            'type' => 'flexi-module',
            'require' => [
                'cubadevops/flexi-contracts' => '*'
            ],
            'autoload' => [
                'psr-4' => [
                    "CubaDevOps\\Flexi\\Modules\\{$moduleName}\\" => '/'
                ]
            ],
            'extra' => [
                'flexi' => [
                    'module-name' => $moduleName,
                    'config-files' => ['Config/services.json']
                ]
            ]
        ];

        file_put_contents($moduleDir . '/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));

        // Create config file
        file_put_contents($moduleDir . '/Config/services.json', '{}');
    }

    private function createInvalidModule(string $moduleName, array $issues = []): void
    {
        $moduleDir = $this->tempModulesPath . '/' . $moduleName;
        mkdir($moduleDir, 0755, true);

        if (!in_array('no_composer_json', $issues)) {
            $composerData = [
                'name' => "cubadevops/flexi-module-{$moduleName}",
                'version' => '1.0.0',
                'type' => 'flexi-module'
            ];

            // Apply various issues
            if (in_array('missing_name', $issues)) {
                unset($composerData['name']);
            }

            if (in_array('missing_version', $issues)) {
                unset($composerData['version']);
            }

            if (in_array('missing_type', $issues)) {
                unset($composerData['type']);
            }

            if (in_array('wrong_package_name', $issues)) {
                $composerData['name'] = 'wrong/package-name';
            }

            if (in_array('wrong_type', $issues)) {
                $composerData['type'] = 'library';
            }

            if (in_array('missing_flexi_dependency', $issues)) {
                $composerData['require'] = ['php' => '^7.4'];
            } else {
                $composerData['require'] = ['cubadevops/flexi-contracts' => '*'];
            }

            if (in_array('missing_autoload', $issues)) {
                // Don't add autoload section
            } else {
                $composerData['autoload'] = [
                    'psr-4' => [
                        "CubaDevOps\\Flexi\\Modules\\{$moduleName}\\" => '/'
                    ]
                ];
            }

            if (in_array('wrong_namespace', $issues)) {
                $composerData['autoload'] = [
                    'psr-4' => [
                        'Wrong\\Namespace\\' => '/'
                    ]
                ];
            }

            // Handle extra/flexi metadata
            if (in_array('wrong_module_name_metadata', $issues) || in_array('missing_config_file', $issues)) {
                $flaxiMeta = [];

                if (in_array('wrong_module_name_metadata', $issues)) {
                    $flaxiMeta['module-name'] = 'wrongname';
                }

                if (in_array('missing_config_file', $issues)) {
                    $flaxiMeta['config-files'] = ['Config/nonexistent.json'];
                }

                $composerData['extra'] = ['flexi' => $flaxiMeta];
            }

            if (in_array('invalid_json', $issues)) {
                file_put_contents($moduleDir . '/composer.json', 'invalid json content');
                return;
            }

            file_put_contents($moduleDir . '/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));
        }
    }

    public function testImplementsHandlerInterface(): void
    {
        $validateModules = $this->createValidateModules();
        $this->assertInstanceOf(HandlerInterface::class, $validateModules);
    }

    public function testConstructorWithDefaultPaths(): void
    {
        $useCase = $this->createValidateModules();
        $this->assertInstanceOf(ValidateModules::class, $useCase);
    }

    public function testConstructorWithCustomPaths(): void
    {
        $customModules = '/custom/modules';
        $useCase = $this->createValidateModules();
        $this->assertInstanceOf(ValidateModules::class, $useCase);
    }

    public function testHandleWithNoModulesDirectory(): void
    {
        // Configure mock to return no modules
        $this->moduleDetector->method('getAllModules')
            ->willReturn([]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(0, $response['total']);
        $this->assertEquals(0, $response['valid']);
        $this->assertEquals(0, $response['invalid']);
        $this->assertEquals(0, $response['warnings']);
        $this->assertEmpty($response['modules']);
    }

    public function testHandleWithEmptyModulesDirectory(): void
    {
        // Configure mock to return no modules (empty directory)
        $this->moduleDetector->method('getAllModules')
            ->willReturn([]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(0, $response['total']);
        $this->assertEquals(0, $response['valid']);
        $this->assertEquals(0, $response['invalid']);
        $this->assertEquals(0, $response['warnings']);
        $this->assertEmpty($response['modules']);
    }

    public function testHandleWithValidModules(): void
    {
        $this->createValidModule('testmodule1');
        $this->createValidModule('testmodule2');

        // Create ModuleInfo objects for the modules
        $modules = [
            new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
                'testmodule1',
                'cubadevops/flexi-module-testmodule1',
                \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
                $this->tempModulesPath . '/testmodule1',
                '1.0.0',
                false,
                []
            ),
            new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
                'testmodule2',
                'cubadevops/flexi-module-testmodule2',
                \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
                $this->tempModulesPath . '/testmodule2',
                '1.0.0',
                false,
                []
            )
        ];

        // Configure mock to return the modules
        $this->moduleDetector->method('getAllModules')
            ->willReturn($modules);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(2, $response['total']);
        $this->assertEquals(2, $response['valid']);
        $this->assertEquals(0, $response['invalid']);
        $this->assertArrayHasKey('testmodule1', $response['modules']);
        $this->assertArrayHasKey('testmodule2', $response['modules']);

        $this->assertTrue($response['modules']['testmodule1']['valid']);
        $this->assertTrue($response['modules']['testmodule2']['valid']);
    }

    public function testHandleWithModuleMissingComposerJson(): void
    {
        $this->createInvalidModule('badmodule', ['no_composer_json']);

        // Create ModuleInfo object for the module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'badmodule',
            'cubadevops/flexi-module-badmodule',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $this->tempModulesPath . '/badmodule',
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(1, $response['total']);
        $this->assertEquals(0, $response['valid']);
        $this->assertEquals(1, $response['invalid']);

        $moduleResult = $response['modules']['badmodule'];
        $this->assertFalse($moduleResult['valid']);
        $this->assertContains('composer.json not found', $moduleResult['errors']);
    }

    public function testHandleWithModuleInvalidJson(): void
    {
        $this->createInvalidModule('invalidjson', ['invalid_json']);

        // Create ModuleInfo object for the module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'invalidjson',
            'cubadevops/flexi-module-invalidjson',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $this->tempModulesPath . '/invalidjson',
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(1, $response['total']);
        $this->assertEquals(0, $response['valid']);
        $this->assertEquals(1, $response['invalid']);

        $moduleResult = $response['modules']['invalidjson'];
        $this->assertFalse($moduleResult['valid']);
        $this->assertCount(1, $moduleResult['errors']);
        $this->assertStringContainsString('Invalid JSON in composer.json', $moduleResult['errors'][0]);
    }

    public function testHandleWithModuleMissingRequiredFields(): void
    {
        $this->createInvalidModule('incomplete', ['missing_name', 'missing_version', 'missing_type', 'missing_autoload']);

        // Create ModuleInfo object for the module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'incomplete',
            'cubadevops/flexi-module-incomplete',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $this->tempModulesPath . '/incomplete',
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(1, $response['total']);
        $this->assertEquals(0, $response['valid']);
        $this->assertEquals(1, $response['invalid']);

        $moduleResult = $response['modules']['incomplete'];
        $this->assertFalse($moduleResult['valid']);

        $expectedErrors = [
            'Missing required field: name',
            'Missing required field: version',
            'Missing required field: type',
            'Missing required field: autoload'
        ];

        foreach ($expectedErrors as $error) {
            $this->assertContains($error, $moduleResult['errors']);
        }
    }

    public function testHandleWithModuleMissingFlexiDependency(): void
    {
        $this->createInvalidModule('nodep', ['missing_flexi_dependency']);

        // Create ModuleInfo object for the module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'nodep',
            'cubadevops/flexi-module-nodep',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $this->tempModulesPath . '/nodep',
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(1, $response['total']);
        $this->assertEquals(0, $response['valid']);
        $this->assertEquals(1, $response['invalid']);

        $moduleResult = $response['modules']['nodep'];
        $this->assertFalse($moduleResult['valid']);
        $this->assertContains('Missing required dependency: cubadevops/flexi-contracts', $moduleResult['errors']);
    }

    public function testHandleWithModuleWarnings(): void
    {
        $this->createInvalidModule('warnings', [
            'wrong_package_name',
            'wrong_type',
            'wrong_namespace'
        ]);

        // Create ModuleInfo object for the module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'warnings',
            'cubadevops/flexi-module-warnings',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $this->tempModulesPath . '/warnings',
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(1, $response['total']);
        $this->assertEquals(1, $response['valid']); // Still valid despite warnings
        $this->assertEquals(0, $response['invalid']);
        $this->assertGreaterThan(0, $response['warnings']);

        $moduleResult = $response['modules']['warnings'];
        $this->assertTrue($moduleResult['valid']);

        $expectedWarnings = [
            'Package name does not follow convention: cubadevops/flexi-module-{name}',
            'Type should be "flexi-module"',
            'Missing flexi metadata in extra section'
        ];

        foreach ($expectedWarnings as $warning) {
            $this->assertContains($warning, $moduleResult['warnings']);
        }
    }

    public function testHandleWithModuleMetadataWarnings(): void
    {
        $this->createInvalidModule('metawarn', [
            'wrong_module_name_metadata',
            'missing_config_file'
        ]);

        // Create ModuleInfo object for the module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'metawarn',
            'cubadevops/flexi-module-metawarn',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $this->tempModulesPath . '/metawarn',
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $moduleResult = $response['modules']['metawarn'];
        $this->assertTrue($moduleResult['valid']);

        $expectedWarnings = [
            "Module name in metadata ('wrongname') doesn't match directory name ('metawarn')",
            'Config file not found: Config/nonexistent.json'
        ];

        foreach ($expectedWarnings as $warning) {
            $this->assertContains($warning, $moduleResult['warnings']);
        }
    }

    public function testHandleWithModuleMissingFlexiMetadata(): void
    {
        $moduleDir = $this->tempModulesPath . '/nometa';
        mkdir($moduleDir, 0755, true);

        $composerData = [
            'name' => 'cubadevops/flexi-module-nometa',
            'version' => '1.0.0',
            'type' => 'flexi-module',
            'require' => ['cubadevops/flexi-contracts' => '*'],
            'autoload' => [
                'psr-4' => [
                    'CubaDevOps\\Flexi\\Modules\\nometa\\' => '/'
                ]
            ]
            // Missing 'extra' section
        ];

        file_put_contents($moduleDir . '/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));

        // Create ModuleInfo object for the module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'nometa',
            'cubadevops/flexi-module-nometa',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $moduleDir,
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $moduleResult = $response['modules']['nometa'];
        $this->assertTrue($moduleResult['valid']);
        $this->assertContains('Missing flexi metadata in extra section', $moduleResult['warnings']);
    }

    public function testHandleWithModuleMissingRecommendedDirectories(): void
    {
        $moduleDir = $this->tempModulesPath . '/nodirs';
        mkdir($moduleDir, 0755, true);

        $composerData = [
            'name' => 'cubadevops/flexi-module-nodirs',
            'version' => '1.0.0',
            'type' => 'flexi-module',
            'require' => ['cubadevops/flexi-contracts' => '*'],
            'autoload' => [
                'psr-4' => [
                    'CubaDevOps\\Flexi\\Modules\\nodirs\\' => '/'
                ]
            ]
        ];

        file_put_contents($moduleDir . '/composer.json', json_encode($composerData, JSON_PRETTY_PRINT));

        // Create ModuleInfo object for the test module
        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'nodirs',
            'cubadevops/flexi-module-nodirs',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
            $moduleDir,
            '1.0.0',
            false,
            []
        );

        // Configure mock to return the module
        $this->moduleDetector->method('getAllModules')
            ->willReturn([$moduleInfo]);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $moduleResult = $response['modules']['nodirs'];
        $this->assertTrue($moduleResult['valid']);

        $expectedWarnings = [
            'Missing recommended directory: Domain',
            'Missing recommended directory: Infrastructure',
            'Missing recommended directory: Config'
        ];

        foreach ($expectedWarnings as $warning) {
            $this->assertContains($warning, $moduleResult['warnings']);
        }
    }

    public function testHandleWithConflictedModuleAddsStateInfo(): void
    {
        $this->createValidModule('hybrid');

        $metadata = [
            'description' => 'Hybrid delivery',
            'local_path' => '/modules/hybrid',
            'vendor_path' => '/vendor/flexi/hybrid',
            'resolution_strategy' => 'local_priority',
        ];

        $moduleInfo = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
            'hybrid',
            'cubadevops/flexi-module-hybrid',
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::mixed(),
            $this->tempModulesPath . '/hybrid',
            '1.0.0',
            true,
            $metadata
        );

        $this->moduleDetector->method('getAllModules')->willReturn([$moduleInfo]);

        $moduleState = new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleState(
            'hybrid',
            true,
            \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::mixed(),
            new \DateTimeImmutable('2025-01-15T09:45:00+00:00'),
            'platform'
        );

        $this->stateManager->method('isModuleActive')->with('hybrid')->willReturn(true);
        $this->stateManager->method('getModuleState')->with('hybrid')->willReturn($moduleState);

        $response = json_decode(
            $this->createValidateModules()->handle(new ValidateModulesCommand())->get('body'),
            true
        );

        $this->assertSame(1, $response['total']);
        $this->assertSame(1, $response['valid']);
        $this->assertSame(0, $response['invalid']);

        $moduleResult = $response['modules']['hybrid'];
        $this->assertTrue($moduleResult['valid']);
        $this->assertTrue($moduleResult['active']);
        $this->assertSame('platform', $moduleResult['state_info']['modified_by']);
        $this->assertContains('Module has conflicts between local and vendor versions', $moduleResult['warnings']);
        $this->assertSame('/modules/hybrid', $moduleResult['conflict_info']['local_path']);
    }

    public function testPathNormalizationInConstructor(): void
    {
        $validateModules = $this->createValidateModules();
        $this->assertInstanceOf(ValidateModules::class, $validateModules);

        $validateModules2 = $this->createValidateModules();
        $this->assertInstanceOf(ValidateModules::class, $validateModules2);
    }

    public function testHandleWithMixedValidAndInvalidModules(): void
    {
        $this->createValidModule('good1');
        $this->createInvalidModule('bad1', ['no_composer_json']);
        $this->createInvalidModule('bad2', ['missing_flexi_dependency']);
        $this->createValidModule('good2');

        // Create ModuleInfo objects for all modules
        $modules = [
            new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
                'good1',
                'cubadevops/flexi-module-good1',
                \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
                $this->tempModulesPath . '/good1',
                '1.0.0',
                false,
                []
            ),
            new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
                'bad1',
                'cubadevops/flexi-module-bad1',
                \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
                $this->tempModulesPath . '/bad1',
                '1.0.0',
                false,
                []
            ),
            new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
                'bad2',
                'cubadevops/flexi-module-bad2',
                \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
                $this->tempModulesPath . '/bad2',
                '1.0.0',
                false,
                []
            ),
            new \CubaDevOps\Flexi\Domain\ValueObjects\ModuleInfo(
                'good2',
                'cubadevops/flexi-module-good2',
                \CubaDevOps\Flexi\Domain\ValueObjects\ModuleType::local(),
                $this->tempModulesPath . '/good2',
                '1.0.0',
                false,
                []
            )
        ];

        // Configure mock to return all modules
        $this->moduleDetector->method('getAllModules')
            ->willReturn($modules);

        $validateModules = $this->createValidateModules();
        $dto = new ValidateModulesCommand();

        $result = $validateModules->handle($dto);

        $this->assertInstanceOf(MessageInterface::class, $result);

        $response = json_decode($result->get('body'), true);
        $this->assertEquals(4, $response['total']);
        $this->assertEquals(2, $response['valid']);
        $this->assertEquals(2, $response['invalid']);

        $this->assertTrue($response['modules']['good1']['valid']);
        $this->assertTrue($response['modules']['good2']['valid']);
        $this->assertFalse($response['modules']['bad1']['valid']);
        $this->assertFalse($response['modules']['bad2']['valid']);
    }
}