<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles;

use CubaDevOps\Flexi\Contracts\Interfaces\EntityInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\ID;

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

    public function toArray(): array
    {
        return [];
    }
}