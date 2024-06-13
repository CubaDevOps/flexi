<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface CliDTOInterface extends DTOInterface
{
    public function usage(): string;
}
