<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Classes;

use Flexi\Domain\ValueObjects\ModuleInfo;
use Flexi\Domain\ValueObjects\ModuleType;
use Flexi\Infrastructure\Classes\LocalModuleDetector;
use Flexi\Infrastructure\Classes\ModuleCacheManager;
use PHPUnit\Framework\TestCase;

final class LocalModuleDetectorTest extends TestCase
{
    private string $modulesDir;
    private ModuleCacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modulesDir = sys_get_temp_dir() . '/local_detector_' . uniqid();
        mkdir($this->modulesDir);
        $cacheDir = sys_get_temp_dir() . '/cache_' . uniqid();
        mkdir($cacheDir);
        $this->cacheManager = new ModuleCacheManager($cacheDir, $this->modulesDir);
        LocalModuleDetector::clearCache();
    }

    protected function tearDown(): void
    {
        LocalModuleDetector::clearCache();
        $this->removeDirectory($this->modulesDir);

        parent::tearDown();
    }

    public function testGetAllModulesParsesComposerMetadata(): void
    {
        $this->createModule('Blog', [
            'name' => 'cubadevops/flexi-module-blog',
            'version' => '1.2.0',
            'description' => 'Blog module',
            'extra' => [
                'flexi' => [
                    'name' => 'Blog',
                    'category' => 'cms'
                ]
            ]
        ]);

        $detector = new LocalModuleDetector($this->cacheManager, $this->modulesDir);

        $modules = $detector->getAllModules();
        $this->assertArrayHasKey('Blog', $modules);

        $info = $modules['Blog'];
        $this->assertInstanceOf(ModuleInfo::class, $info);
        $this->assertTrue($info->getType()->equals(ModuleType::local()));
        $this->assertSame('Blog', $info->getName());
        $this->assertSame('cubadevops/flexi-module-blog', $info->getPackage());
        $this->assertSame('1.2.0', $info->getVersion());
        $this->assertSame('Blog module', $info->getMetadata()['description']);
    }

    public function testNormalizeModuleNameAndDerivedMetadataWhenComposerMissingFields(): void
    {
        mkdir($this->modulesDir . '/custom');
        file_put_contents(
            $this->modulesDir . '/custom/composer.json',
            json_encode([
                'extra' => [
                    'flexi' => []
                ]
            ], JSON_PRETTY_PRINT)
        );

        $detector = new LocalModuleDetector($this->cacheManager, $this->modulesDir);

        $this->assertTrue($detector->isModuleInstalled('custom'));

        $info = $detector->getModuleInfo('custom');
        $this->assertNotNull($info);
        $this->assertSame('Custom', $info->getName());
        $this->assertSame('flexi/flexi-module-custom', $info->getPackage());
        $this->assertSame('unknown', $info->getVersion());
        $this->assertSame('flexi-module', $info->getMetadata()['type']);
    }

    public function testStatisticsReflectTypeCountsAndTotals(): void
    {
        $this->createModule('Reports', [
            'name' => 'cubadevops/flexi-module-reports',
            'description' => 'Reporting module',
            'type' => 'analytics',
            'extra' => [
                'flexi' => [
                    'category' => 'analytics'
                ]
            ]
        ]);
        $this->createModule('Legacy', []);

        $detector = new LocalModuleDetector($this->cacheManager, $this->modulesDir);

        $stats = $detector->getModuleStatistics();
        $this->assertSame(2, $stats['total']);
        $this->assertSame(2, $stats['local']);
        $this->assertSame(0, $stats['vendor']);
        ksort($stats['by_type']);
        $this->assertSame(['analytics' => 1, 'flexi-module' => 1], $stats['by_type']);
    }

    private function createModule(string $name, array $composerData): void
    {
        $modulePath = $this->modulesDir . '/' . $name;
        mkdir($modulePath);

        $data = $composerData;
        if (!array_key_exists('name', $data)) {
            unset($data['name']);
        }
        file_put_contents(
            $modulePath . '/composer.json',
            json_encode($data, JSON_PRETTY_PRINT)
        );
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            if (file_exists($path)) {
                unlink($path);
            }
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            unlink($itemPath);
        }

        rmdir($path);
    }
}
