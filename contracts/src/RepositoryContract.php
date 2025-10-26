<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Contracts;

/**
 * Contract for Repository Pattern
 * Standard interface for data persistence.
 */
interface RepositoryContract
{
    /**
     * Find entity by ID.
     */
    public function findById($id);

    /**
     * Save entity.
     */
    public function save($entity): void;

    /**
     * Delete entity.
     */
    public function delete($entity): void;

    /**
     * Find entities by criteria.
     */
    public function findByCriteria(array $criteria): array;
}
