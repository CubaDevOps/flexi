<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

interface CliDTOInterface extends DTOInterface
{
    public function usage(): string;
}
