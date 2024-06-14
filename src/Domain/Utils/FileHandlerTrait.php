<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

trait FileHandlerTrait
{
    use OSDetector;

    public function writeToFile(string $file_path, string $content, int $flags = 0): void
    {
        $this->ensureFileExists($file_path);
        if (false === file_put_contents($file_path, $content, $flags)) {
            throw new \RuntimeException("Could not write to file: $file_path");
        }
    }

    public function ensureFileExists(string &$file_path): void
    {
        $file_path = $this->normalize($file_path);

        $directory = dirname($file_path);
        if (!is_dir($directory) && (!mkdir($directory, 0750, true) && !is_dir($directory))) {
            throw new \RuntimeException("Could not create directory: $directory");
        }

        if (file_exists($file_path)) {
            return;
        }

        try {
            $fileHandle = fopen($file_path, 'wb');

            if (false === $fileHandle) {
                throw new \RuntimeException('Could not open file for writing.');
            }

            fclose($fileHandle);
        } catch (\Exception $e) {
            throw new \RuntimeException('An error occurred while creating the file: ' . $e->getMessage());
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

    public function readFromFile(string $file_path): string
    {
        $this->ensureFileExists($file_path);
        $contents = file_get_contents($file_path);

        if (false === $contents) {
            throw new \RuntimeException("Could not read from file: $file_path");
        }

        return $contents;
    }
}
