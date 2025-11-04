<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces;

use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\Credentials;

/**
 * CredentialsVerifierInterface - Verifies credentials against stored hash
 *
 * Implementations should use secure password verification methods:
 * - password_verify() for bcrypt/argon2 hashes
 * - hash_equals() to prevent timing attacks
 *
 * This interface is agnostic to the specific hashing algorithm used.
 * Different implementations can support different algorithms.
 */
interface CredentialsVerifierInterface
{
    /**
     * Verify provided credentials against stored password hash
     *
     * Security considerations:
     * - Must use hash_equals() to prevent timing attacks
     * - Should support multiple hash algorithms (bcrypt, argon2, etc.)
     * - Must never log or store the plain text password
     * - Should implement rate limiting via external mechanism (middleware, cache)
     *
     * @param Credentials $credentials   The credentials to verify
     * @param string      $password_hash The stored password hash from repository
     *
     * @return bool True if credentials match the hash, false otherwise
     */
    public function verify(Credentials $credentials, string $password_hash): bool;
}
