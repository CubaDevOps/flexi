<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\HealthCheck\Domain\Entities;

use CubaDevOps\Flexi\Contracts\Interfaces\EntityInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\ID;

/**
 * Entity representing version information.
 * This is a placeholder entity used by VersionRepository when no specific entity is needed.
 */
class VersionEntity implements EntityInterface
{
    private ID $id;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->id = new ID('version-entity');
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ID
    {
        return $this->id;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id->getValue(),
            'created_at' => $this->createdAt->format(DATE_ATOM),
            'updated_at' => $this->updatedAt->format(DATE_ATOM),
        ];
    }
}