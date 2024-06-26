<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface LogRepositoryInterface
{
    public function save(LogInterface $log): void;
}
