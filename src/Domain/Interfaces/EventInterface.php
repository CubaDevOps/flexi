<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use Psr\EventDispatcher\StoppableEventInterface;

interface EventInterface extends DTOInterface, StoppableEventInterface
{
    public function getName(): string;

    public function occurredOn(): \DateTimeImmutable;

    public function firedBy(): string;

    public function serialize(): string;
}
