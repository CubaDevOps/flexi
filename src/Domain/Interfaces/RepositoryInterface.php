<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Interfaces;

use CubaDevOps\Flexi\Domain\ValueObjects\ID;

interface RepositoryInterface
{
    public function retrieveValue(
        CriteriaInterface $criteria
    ): ValueObjectInterface;

    public function get(ID $id): EntityInterface;

    public function create(EntityInterface $entity): void;

    public function update(EntityInterface $entity): void;

    public function delete(ID $entity_id): void;

    public function getAll(): CollectionInterface;

    public function search(CriteriaInterface $criteria): CollectionInterface;
}
