<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

interface CliDTOContract extends DTOContract
{
    public function usage(): string;
}
