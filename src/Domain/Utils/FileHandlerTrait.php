<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Utils;

trait FileHandlerTrait
{
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
            throw new \RuntimeException('An error occurred while creating the file: '.$e->getMessage());
        }
    }

    public function normalize(string $relative_path): string
    {
        if ($this->isAbsolutePath($relative_path)) {
            return $relative_path;
        }

        $rootDir = dirname(__DIR__, 3);
        $fullPath = $rootDir.DIRECTORY_SEPARATOR.$relative_path;
        $segments = explode(DIRECTORY_SEPARATOR, $fullPath);
        $normalizedSegments = [];

        foreach ($segments as $segment) {
            if ('' === $segment || '.' === $segment) {
                continue;
            }

            if ('..' === $segment) {
                array_pop($normalizedSegments);
            } else {
                $normalizedSegments[] = $segment;
            }
        }

        return DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $normalizedSegments);
    }

    private function isAbsolutePath(string $path): bool
    {
        return 0 === strpos($path, DIRECTORY_SEPARATOR) || (strlen($path) > 1 && ':' === $path[1]);
    }
}
