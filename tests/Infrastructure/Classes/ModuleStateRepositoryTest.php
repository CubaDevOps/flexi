<?php

declare(strict_types=1);

namespace Flexi\Test\Infrastructure\Classes;

use Flexi\Domain\ValueObjects\ModuleState;
use Flexi\Domain\ValueObjects\ModuleType;
use Flexi\Infrastructure\Classes\ModuleStateRepository;
use PHPUnit\Framework\TestCase;

final class ModuleStateRepositoryTest extends TestCase
{
    private string $tempDir;
    private string $currentPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/module_state_repo_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testSaveAndFindModuleStatePersistsData(): void
    {
        $path = $this->tempDir . '/nested/modules-state.json';
        $repository = new ModuleStateRepository($path);

        $state = ModuleState::createActive('news', ModuleType::local());
        $this->assertTrue($repository->save($state));

        $this->assertFileExists($path);
        $stored = $repository->find('news');

        $this->assertInstanceOf(ModuleState::class, $stored);
        $this->assertTrue($stored->isActive());
        $this->assertTrue($stored->getType()->equals(ModuleType::local()));
    }

    public function testSaveAllCountAndFindAllReturnStoredStates(): void
    {
        $repository = $this->newRepository();

        $states = [
            ModuleState::createInactive('alpha', ModuleType::local()),
            ModuleState::createActive('beta', ModuleType::vendor()),
        ];

        $this->assertTrue($repository->saveAll($states));
        $this->assertSame(2, $repository->count());

        $all = $repository->findAll();
        $this->assertCount(2, $all);
        $this->assertFalse($all['alpha']->isActive());
        $this->assertTrue($all['beta']->isActive());
    }

    public function testDeleteAndClearRemoveEntries(): void
    {
        $repository = $this->newRepository();
        $repository->save(ModuleState::createActive('delta', ModuleType::local()));
        $repository->save(ModuleState::createInactive('gamma', ModuleType::vendor()));

        $this->assertTrue($repository->delete('gamma'));
        $this->assertFalse($repository->exists('gamma'));
        $this->assertFalse($repository->delete('missing'));

        $this->assertTrue($repository->clear());
        $this->assertSame([], $repository->findAll());
    }

    public function testImportDataMergesWhenNotOverwriting(): void
    {
        $repository = $this->newRepository();
        $repository->save(ModuleState::createActive('core', ModuleType::local()));

        $payload = [
            'version' => '2.0.0',
            'lastSync' => '2025-11-18T10:00:00+00:00',
            'modules' => [
                'extra' => [
                    'active' => false,
                    'type' => 'vendor',
                    'lastModified' => '2025-11-17T10:00:00+00:00',
                    'modifiedBy' => 'importer',
                    'metadata' => ['reason' => 'sync'],
                ],
            ],
        ];

        $this->assertTrue($repository->importData($payload, false));

        $all = $repository->findAll();
        $this->assertFalse($repository->exists('core'));
        $this->assertArrayHasKey('extra', $all);
        $this->assertFalse($all['extra']->isActive());
        $this->assertSame('importer', $all['extra']->getModifiedBy());
    }

    public function testImportDataWithOverwriteReplacesExistingData(): void
    {
        $repository = $this->newRepository();
        $repository->save(ModuleState::createActive('legacy', ModuleType::local()));

        $payload = [
            'version' => '3.0.0',
            'lastSync' => '2025-11-18T10:30:00+00:00',
            'modules' => [
                'fresh' => [
                    'active' => true,
                    'type' => 'mixed',
                    'lastModified' => '2025-11-18T10:30:00+00:00',
                    'modifiedBy' => 'overwrite',
                    'metadata' => [],
                ],
            ],
        ];

        $this->assertTrue($repository->importData($payload, true));

        $this->assertNull($repository->find('legacy'));
        $this->assertNotNull($repository->find('fresh'));
    }

    public function testGetMetadataReflectsStoredInformation(): void
    {
        $repository = $this->newRepository();
        $repository->save(ModuleState::createInactive('target', ModuleType::vendor()));

        $metadata = $repository->getMetadata();

        $this->assertTrue($metadata['fileExists']);
        $this->assertSame(1, $metadata['moduleCount']);
        $this->assertSame($this->currentPath, $metadata['filePath']);
        $this->assertGreaterThan(0, $metadata['fileSize']);
        $this->assertNotNull($metadata['lastSync']);
    }

    public function testBackupCreatesTimestampedCopy(): void
    {
        $repository = $this->newRepository();
        $repository->save(ModuleState::createActive('web', ModuleType::mixed()));

        $this->assertTrue($repository->backup());

        $matches = glob($this->currentPath . '.backup.*');
        $this->assertNotFalse($matches);
        $this->assertNotEmpty($matches);
        $this->assertFileExists($matches[0]);
    }

    public function testLoadFromCorruptedFileFallsBackToDefault(): void
    {
        $path = $this->tempDir . '/corrupted-' . uniqid() . '.json';
        file_put_contents($path, '{ invalid');

        $repository = new ModuleStateRepository($path);

        $this->assertSame([], $repository->findAll());
        $exported = $repository->exportData();
        $this->assertSame('1.0.0', $exported['version']);
        $this->assertSame([], $exported['modules']);
    }

    private function newRepository(): ModuleStateRepository
    {
        $this->currentPath = $this->tempDir . '/' . uniqid('states_', true) . '.json';
        return new ModuleStateRepository($this->currentPath);
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
