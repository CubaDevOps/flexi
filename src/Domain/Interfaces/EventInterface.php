<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

interface EventInterface extends DTOInterface
{
    public function getName(): string;

    public function occurredOn(): \DateTimeImmutable;

    public function firedBy(): string;

    public function serialize(): string;
}
