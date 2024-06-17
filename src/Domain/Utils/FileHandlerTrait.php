<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

use RuntimeException;

trait FileHandlerTrait
{
    use OSDetector;

    /**
     * @param string $file_path
     * @param string $content
     * @param int $flags
     * @param bool $try_create_it
     * @return void
     * @throws RuntimeException
     */
    public function writeToFile(string $file_path, string $content, int $flags = 0, bool $try_create_it = false): void
    {
        $this->ensureFileExists($file_path, $try_create_it);
        if (false === file_put_contents($file_path, $content, $flags)) {
            throw new RuntimeException("Could not write to file: $file_path");
        }
    }

    /**
     * @param string $file_path
     * @param bool $try_create_it
     * @return void
     * @throws RuntimeException
     */
    public function ensureFileExists(string &$file_path, bool $try_create_it = false): void
    {
        $file_path = $this->normalize($file_path);

        if ($try_create_it) {
            $this->createFileIfNotExists($file_path);
        }

        if (!file_exists($file_path)) {
            throw new RuntimeException('File ' . $file_path . ' does not exist');
        }
    }

    public function normalize(string $relative_path): string
    {
        if ($this->isAbsolutePath($relative_path)) {
            return $relative_path;
        }

        $rootDir = dirname(__DIR__, 3);
        $fullPath = $rootDir . DIRECTORY_SEPARATOR . $relative_path;
        $segments = preg_split('/[\/\\\\]+/', $fullPath);
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ($segment === '..') {
                array_pop($normalizedSegments);
            } else {
                $normalizedSegments[] = $segment;
            }
        }

        $normalizedPath = implode(DIRECTORY_SEPARATOR, $normalizedSegments);

        return $this->isWindows() ? $normalizedPath : DIRECTORY_SEPARATOR . $normalizedPath;
    }

    private function isAbsolutePath(string $path): bool
    {
        if ($this->isUnix()) {
            // Check for Unix absolute paths
            return str_starts_with($path, '/') || str_starts_with($path, '~');
        }

        if ($this->isWindows()) {
            // Check for Windows absolute paths
            return preg_match('/^[a-zA-Z]:\\\\/', $path) || str_starts_with($path, '\\\\');
        }

        return false;
    }

    /**
     * @param string $file_path
     * @return void
     * @throws RuntimeException
     */
    private function createFileIfNotExists(string $file_path): void
    {
        $directory = dirname($file_path);
        $this->createDirectoryIfNotExist($directory);

        if (!file_exists($file_path)) {
            $fileHandle = fopen($file_path, 'wb');

            if ($fileHandle === false) {
                throw new RuntimeException("Could not open file for writing: $file_path");
            }

            fclose($fileHandle);
        }
    }

    /**
     * @param string $dir_path
     * @return void
     * @throws RuntimeException
     */
    private function createDirectoryIfNotExist(string $dir_path): void
    {
        if (!is_dir($dir_path) && !mkdir($dir_path, 0750, true) && !is_dir($dir_path)) {
            throw new RuntimeException("Could not create directory: $dir_path");
        }
    }

    /**
     * @param string $file_path
     * @param bool $try_create_it
     * @return string
     * @throws RuntimeException
     */
    public function readFromFile(string $file_path, bool $try_create_it = false): string
    {
        $this->ensureFileExists($file_path, $try_create_it);
        $contents = file_get_contents($file_path);

        if (false === $contents) {
            throw new RuntimeException("Could not read from file: $file_path");
        }

        return $contents;
    }
}
