<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Entities;

use CubaDevOps\Flexi\Domain\Interfaces\EntityInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\ID;

class DummyEntity implements EntityInterface
{
    public function getId(): ID
    {
        return new ID('dummy_id');
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        // dummy implementation
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [];
    }
}
