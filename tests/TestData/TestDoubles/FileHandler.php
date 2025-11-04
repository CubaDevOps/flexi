<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use Flexi\Contracts\Classes\Traits\FileHandlerTrait;

class FileHandler
{
    use FileHandlerTrait;

    public function createFileIfNotExist(string $file_path): void
    {
        $file_path = $this->normalize($file_path);
        if ($this->fileExists($file_path)) {
            return;
        }
        $this->createFile($file_path);
    }
}
