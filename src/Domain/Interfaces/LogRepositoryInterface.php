<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface LogRepositoryInterface
{
    public function save(LogInterface $log): void;
}
