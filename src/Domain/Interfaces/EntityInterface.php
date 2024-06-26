<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\ValueObjects\ID;

interface EntityInterface
{
    public function getId(): ID;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getUpdatedAt(): \DateTimeImmutable;

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void;

    public function toArray(): array;
}
