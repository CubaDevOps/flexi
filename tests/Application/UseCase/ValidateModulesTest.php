<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Application\UseCase;

use CubaDevOps\Flexi\Application\Commands\ValidateModulesCommand;
use CubaDevOps\Flexi\Application\UseCase\ValidateModules;
use Flexi\Contracts\Interfaces\HandlerInterface;
use Flexi\Contracts\Interfaces\MessageInterface;
use PHPUnit\Framework\TestCase;

class ValidateModulesTest extends TestCase
{
    private string $tempDir;
    private string $tempModulesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir();
        $this->tempModulesPath = $this->tempDir . '/test_validate_modules_' . uniqid();

        mkdir($this->tempModulesPath, 0755, true);
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
        $validateModules = new ValidateModules();
        $this->assertInstanceOf(HandlerInterface::class, $validateModules);
    }

    public function testConstructorWithDefaultPaths(): void
    {
        $useCase = new ValidateModules();
        $this->assertInstanceOf(ValidateModules::class, $useCase);
    }

    public function testConstructorWithCustomPaths(): void
    {
        $customModules = '/custom/modules';
        $useCase = new ValidateModules($customModules);
        $this->assertInstanceOf(ValidateModules::class, $useCase);
    }

    public function testHandleWithNoModulesDirectory(): void
    {
        // Remove modules directory
        rmdir($this->tempModulesPath);

        $validateModules = new ValidateModules($this->tempModulesPath);
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
        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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
            'PSR-4 namespace should be: CubaDevOps\\Flexi\\Modules\\warnings\\'
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

        $validateModules = new ValidateModules($this->tempModulesPath);
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

    public function testPathNormalizationInConstructor(): void
    {
        $validateModules = new ValidateModules('./modules/');
        $this->assertInstanceOf(ValidateModules::class, $validateModules);

        $validateModules2 = new ValidateModules('./modules');
        $this->assertInstanceOf(ValidateModules::class, $validateModules2);
    }

    public function testHandleWithMixedValidAndInvalidModules(): void
    {
        $this->createValidModule('good1');
        $this->createInvalidModule('bad1', ['no_composer_json']);
        $this->createInvalidModule('bad2', ['missing_flexi_dependency']);
        $this->createValidModule('good2');

        $validateModules = new ValidateModules($this->tempModulesPath);
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