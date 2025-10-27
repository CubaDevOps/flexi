<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts\Interfaces;

/**
 * Contract for Repository Pattern
 * Standard interface for data persistence.
 */
interface RepositoryInterface
{

    public function retrieveValue(CriteriaInterface $criteria): ?ValueObjectInterface;


    /**
     * Find entity by ID.
     */
    public function findById($id): ?EntityInterface;

    /**
     * Save entity.
     */
    public function save(EntityInterface $entity): void;

    /**
     * Delete entity.
     */
    public function delete(EntityInterface $entity): void;

    /**
     * Find entities by criteria.
     */
    public function search(CriteriaInterface $criteria): CollectionInterface;
}
