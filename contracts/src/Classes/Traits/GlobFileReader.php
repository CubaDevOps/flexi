<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Classes\Traits;

trait GlobFileReader
{
    use FileHandlerTrait;

    /**
     * @return string[]
     */
    public function readGlob(string $glob_path): array
    {
        $normalized_path = $this->normalize($glob_path);

        return glob($normalized_path, GLOB_BRACE | GLOB_NOSORT) ?: [];
    }
}
