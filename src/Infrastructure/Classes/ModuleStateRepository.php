<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\ValueObjects\ModuleState;
use DateTimeImmutable;

/**
 * Repository for persisting module states to file system.
 *
 * Handles reading and writing module state data to var/modules-state.json,
 * providing atomic operations and backup functionality.
 */
class ModuleStateRepository
{
    private string $stateFilePath;
    private array $loadedData;
    private bool $dataLoaded = false;

    public function __construct(string $stateFilePath = './var/modules-state.json')
    {
        $this->stateFilePath = $stateFilePath;
        $this->loadedData = [];
    }

    /**
     * Save a module state.
     */
    public function save(ModuleState $state): bool
    {
        $this->ensureDataLoaded();

        $this->loadedData['modules'][$state->getModuleName()] = $state->toArray();
        $this->loadedData['lastSync'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);

        return $this->writeToFile();
    }

    /**
     * Find a module state by name.
     */
    public function find(string $moduleName): ?ModuleState
    {
        $this->ensureDataLoaded();

        if (!isset($this->loadedData['modules'][$moduleName])) {
            return null;
        }

        return ModuleState::fromArray($moduleName, $this->loadedData['modules'][$moduleName]);
    }

    /**
     * Get all module states.
     *
     * @return ModuleState[]
     */
    public function findAll(): array
    {
        $this->ensureDataLoaded();

        $states = [];
        foreach ($this->loadedData['modules'] ?? [] as $moduleName => $moduleData) {
            $states[$moduleName] = ModuleState::fromArray($moduleName, $moduleData);
        }

        return $states;
    }

    /**
     * Check if a module state exists.
     */
    public function exists(string $moduleName): bool
    {
        $this->ensureDataLoaded();
        return isset($this->loadedData['modules'][$moduleName]);
    }

    /**
     * Delete a module state.
     */
    public function delete(string $moduleName): bool
    {
        $this->ensureDataLoaded();

        if (!isset($this->loadedData['modules'][$moduleName])) {
            return false;
        }

        unset($this->loadedData['modules'][$moduleName]);
        $this->loadedData['lastSync'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);

        return $this->writeToFile();
    }

    /**
     * Save multiple module states at once.
     *
     * @param ModuleState[] $states
     */
    public function saveAll(array $states): bool
    {
        $this->ensureDataLoaded();

        foreach ($states as $state) {
            $this->loadedData['modules'][$state->getModuleName()] = $state->toArray();
        }

        $this->loadedData['lastSync'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);

        return $this->writeToFile();
    }

    /**
     * Clear all module states.
     */
    public function clear(): bool
    {
        $this->loadedData = $this->getDefaultData();
        return $this->writeToFile();
    }

    /**
     * Get count of stored module states.
     */
    public function count(): int
    {
        $this->ensureDataLoaded();
        return count($this->loadedData['modules'] ?? []);
    }

    /**
     * Get metadata about the state file.
     */
    public function getMetadata(): array
    {
        $this->ensureDataLoaded();

        return [
            'version' => $this->loadedData['version'] ?? '1.0.0',
            'lastSync' => $this->loadedData['lastSync'] ?? null,
            'moduleCount' => $this->count(),
            'fileExists' => file_exists($this->stateFilePath),
            'filePath' => $this->stateFilePath,
            'fileSize' => file_exists($this->stateFilePath) ? filesize($this->stateFilePath) : 0,
        ];
    }

    /**
     * Export all data for backup.
     */
    public function exportData(): array
    {
        $this->ensureDataLoaded();
        return $this->loadedData;
    }

    /**
     * Import data from backup.
     */
    public function importData(array $data, bool $overwrite = false): bool
    {
        if (!$overwrite) {
            $this->ensureDataLoaded();
            // Merge with existing data
            $this->loadedData = array_merge($this->loadedData, $data);
            if (isset($data['modules'])) {
                $this->loadedData['modules'] = array_merge(
                    $this->loadedData['modules'] ?? [],
                    $data['modules']
                );
            }
        } else {
            $this->loadedData = $data;
        }

        $this->loadedData['lastSync'] = (new DateTimeImmutable())->format(DateTimeImmutable::ATOM);
        $this->dataLoaded = true;

        return $this->writeToFile();
    }

    /**
     * Create a backup of the current state file.
     */
    public function backup(): bool
    {
        if (!file_exists($this->stateFilePath)) {
            return false;
        }

        $backupPath = $this->stateFilePath . '.backup.' . date('Y-m-d-H-i-s');
        return copy($this->stateFilePath, $backupPath);
    }

    /**
     * Ensure state data is loaded from file.
     */
    private function ensureDataLoaded(): void
    {
        if ($this->dataLoaded) {
            return;
        }

        $this->loadFromFile();
    }

    /**
     * Load data from state file.
     */
    private function loadFromFile(): void
    {
        if (!file_exists($this->stateFilePath)) {
            $this->loadedData = $this->getDefaultData();
            $this->dataLoaded = true;
            return;
        }

        try {
            $content = file_get_contents($this->stateFilePath);
            if ($content === false) {
                $this->loadedData = $this->getDefaultData();
            } else {
                $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
                $this->loadedData = $this->validateAndFixData($decoded);
            }
        } catch (\JsonException $e) {
            // If file is corrupted, start with default data
            $this->loadedData = $this->getDefaultData();
        }

        $this->dataLoaded = true;
    }

    /**
     * Write current data to state file.
     */
    private function writeToFile(): bool
    {
        try {
            // Ensure directory exists
            $dir = dirname($this->stateFilePath);
            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                    return false;
                }
            }

            $json = json_encode($this->loadedData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $tempFile = $this->stateFilePath . '.tmp';

            // Write to temporary file first for atomic operation
            if (file_put_contents($tempFile, $json) === false) {
                return false;
            }

            // Atomic rename
            if (!rename($tempFile, $this->stateFilePath)) {
                unlink($tempFile);
                return false;
            }

            return true;
        } catch (\JsonException $e) {
            return false;
        }
    }

    /**
     * Get default data structure.
     */
    private function getDefaultData(): array
    {
        return [
            'version' => '1.0.0',
            'lastSync' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'modules' => [],
        ];
    }

    /**
     * Validate and fix loaded data structure.
     */
    private function validateAndFixData(array $data): array
    {
        $fixed = $this->getDefaultData();

        if (isset($data['version'])) {
            $fixed['version'] = $data['version'];
        }

        if (isset($data['lastSync'])) {
            $fixed['lastSync'] = $data['lastSync'];
        }

        if (isset($data['modules']) && is_array($data['modules'])) {
            $fixed['modules'] = $data['modules'];
        }

        return $fixed;
    }
}