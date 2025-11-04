<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces;

/**
 * CredentialsRepositoryInterface - Agnostic to storage implementation
 *
 * This interface defines how to retrieve user credentials from any source:
 * - Database (SQL, NoSQL)
 * - File system (JSON, CSV, etc.)
 * - LDAP directory
 * - Remote service
 * - Cache
 *
 * Implementations must return credentials with password hash (never plain text)
 */
interface CredentialsRepositoryInterface
{
    /**
     * Find user credentials by username
     *
     * @param string $username The username to search for
     *
     * @return array{username: string, password_hash: string, user_id: int|string, ...}|null
     *         Returns null if user not found
     *         Returns array with at minimum: username, password_hash, user_id
     *         Additional fields (email, full_name, etc.) are optional
     */
    public function findByUsername(string $username): ?array;
}
