<?php

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface MessageInterface extends DTOInterface
{
    public function createdAt(): \DateTimeImmutable;
}
