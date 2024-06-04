<?php

namespace CubaDevOps\Flexi\Domain\Utils;

trait GlobFileReader
{
    use FileHandlerTrait;

    /**
     * @param string $glob_path
     * @return string[]
     */
    public function readGlob(string $glob_path): array
    {
        $normalized_path = $this->normalize($glob_path);
        return glob($normalized_path, GLOB_BRACE | GLOB_NOSORT) ?: [];
    }
}
