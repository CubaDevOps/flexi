<?php

declare(strict_types=1);

namespace Flexi\Infrastructure\Classes;

use Flexi\Domain\Interfaces\ModuleEnvironmentManagerInterface;
use Flexi\Contracts\Interfaces\ConfigurationInterface;
use Dotenv\Dotenv;

/**
 * Manages environment variables for modules.
 *
 * Handles reading module-specific .env files and integrating them
 * with the main application .env file during module activation/deactivation.
 */
class ModuleEnvironmentManager implements ModuleEnvironmentManagerInterface
{
    private ConfigurationInterface $configuration;
    private string $mainEnvPath;

    private const MODULE_ENV_START_MARKER = '# === MODULE %s ENVIRONMENT VARIABLES ===';
    private const MODULE_ENV_END_MARKER = '# === END MODULE %s ENVIRONMENT VARIABLES ===';
    private const MODULE_ENV_FILE = '.env';

    public function __construct(ConfigurationInterface $configuration)
    {
        $this->configuration = $configuration;
        $this->mainEnvPath = $this->configuration->get('ROOT_DIR') . '/.env';
    }

    /**
     * {@inheritDoc}
     */
    public function readModuleEnvironment(string $modulePath, string $moduleName): array
    {
        $moduleEnvPath = $this->getModuleEnvFilePath($modulePath);

        if (!$this->hasModuleEnvFile($modulePath)) {
            return [];
        }

        try {
            $envVars = [];
            $lines = file($moduleEnvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            if ($lines === false) {
                return [];
            }

            foreach ($lines as $line) {
                $line = trim($line);

                // Skip comments and empty lines
                if (empty($line) || strpos($line, '#') === 0) {
                    continue;
                }

                // Parse key=value pairs
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value, '"\'');

                    if (!empty($key)) {
                        $envVars[$key] = $value;
                    }
                }
            }

            return $envVars;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function addModuleEnvironment(string $moduleName, array $envVars): bool
    {
        if (empty($envVars)) {
            return true;
        }

        // Check if module environment already exists
        if ($this->hasModuleEnvironment($moduleName)) {
            return $this->updateModuleEnvironment($moduleName, $envVars);
        }

        try {
            $envContent = $this->buildModuleEnvironmentBlock($moduleName, $envVars);

            // Append to main .env file
            $result = file_put_contents($this->mainEnvPath, PHP_EOL . $envContent, FILE_APPEND | LOCK_EX);

            return $result !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function removeModuleEnvironment(string $moduleName): bool
    {
        if (!$this->hasModuleEnvironment($moduleName)) {
            return true;
        }

        try {
            $envContent = file_get_contents($this->mainEnvPath);
            if ($envContent === false) {
                return false;
            }

            $startMarker = sprintf(self::MODULE_ENV_START_MARKER, strtoupper($moduleName));
            $endMarker = sprintf(self::MODULE_ENV_END_MARKER, strtoupper($moduleName));

            $startPos = strpos($envContent, $startMarker);
            $endPos = strpos($envContent, $endMarker);

            if ($startPos !== false && $endPos !== false) {
                // Remove the entire block including markers and surrounding newlines
                $blockStart = $startPos;
                $blockEnd = $endPos + strlen($endMarker);

                // Remove trailing newline if present
                if (isset($envContent[$blockEnd]) && $envContent[$blockEnd] === PHP_EOL) {
                    $blockEnd++;
                }

                // Remove leading newlines before the block
                while ($blockStart > 0 && $envContent[$blockStart - 1] === PHP_EOL) {
                    $blockStart--;
                }

                $newContent = substr($envContent, 0, $blockStart) . substr($envContent, $blockEnd);

                // Clean up multiple consecutive newlines
                $newContent = preg_replace('/\n{3,}/', "\n\n", $newContent);

                $result = file_put_contents($this->mainEnvPath, $newContent, LOCK_EX);
                return $result !== false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function hasModuleEnvironment(string $moduleName): bool
    {
        try {
            $envContent = file_get_contents($this->mainEnvPath);
            if ($envContent === false) {
                return false;
            }

            $startMarker = sprintf(self::MODULE_ENV_START_MARKER, strtoupper($moduleName));
            return strpos($envContent, $startMarker) !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getModuleEnvironment(string $moduleName): array
    {
        if (!$this->hasModuleEnvironment($moduleName)) {
            return [];
        }

        try {
            $envContent = file_get_contents($this->mainEnvPath);
            if ($envContent === false) {
                return [];
            }

            $startMarker = sprintf(self::MODULE_ENV_START_MARKER, strtoupper($moduleName));
            $endMarker = sprintf(self::MODULE_ENV_END_MARKER, strtoupper($moduleName));

            $startPos = strpos($envContent, $startMarker);
            $endPos = strpos($envContent, $endMarker);

            if ($startPos !== false && $endPos !== false) {
                $blockContent = substr($envContent, $startPos + strlen($startMarker), $endPos - $startPos - strlen($startMarker));
                return $this->parseEnvironmentVariables($blockContent);
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateModuleEnvironment(string $moduleName, array $newEnvVars): bool
    {
        if (!$this->hasModuleEnvironment($moduleName)) {
            return $this->addModuleEnvironment($moduleName, $newEnvVars);
        }

        try {
            $currentVars = $this->getModuleEnvironment($moduleName);

            // Preserve user modifications: current values override new ones
            // Only add new variables that don't exist in current environment
            $mergedVars = array_merge($newEnvVars, $currentVars);

            // Remove old block and add updated one
            if (!$this->removeModuleEnvironment($moduleName)) {
                return false;
            }

            return $this->addModuleEnvironment($moduleName, $mergedVars);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getModuleEnvFilePath(string $modulePath): string
    {
        return rtrim($modulePath, '/') . '/' . self::MODULE_ENV_FILE;
    }

    /**
     * {@inheritDoc}
     */
    public function hasModuleEnvFile(string $modulePath): bool
    {
        return file_exists($this->getModuleEnvFilePath($modulePath));
    }

    /**
     * Build environment variable block for a module.
     */
    private function buildModuleEnvironmentBlock(string $moduleName, array $envVars): string
    {
        $upperModuleName = strtoupper($moduleName);
        $lines = [];

        $lines[] = '';
        $lines[] = sprintf(self::MODULE_ENV_START_MARKER, $upperModuleName);
        $lines[] = "# Environment variables for module: {$moduleName}";
        $lines[] = "# You can modify these values as needed for your environment";

        foreach ($envVars as $key => $value) {
            // Wrap values with spaces or special characters in quotes
            if (strpos($value, ' ') !== false || strpos($value, '#') !== false || strpos($value, '"') !== false) {
                $value = '"' . addcslashes($value, '"') . '"';
            }
            $lines[] = "{$key}={$value}";
        }

        $lines[] = sprintf(self::MODULE_ENV_END_MARKER, $upperModuleName);
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    /**
     * Parse environment variables from a content string.
     */
    private function parseEnvironmentVariables(string $content): array
    {
        $envVars = [];
        $lines = explode(PHP_EOL, $content);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, '"\'');

                if (!empty($key)) {
                    $envVars[$key] = $value;
                }
            }
        }

        return $envVars;
    }
}