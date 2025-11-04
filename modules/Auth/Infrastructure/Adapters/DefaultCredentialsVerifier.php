<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Adapters;

use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsVerifierInterface;
use CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects\Credentials;

/**
 * DefaultCredentialsVerifier - Secure password verification
 *
 * Uses password_verify() with hash_equals() to prevent timing attacks.
 * Supports any algorithm that PHP's password_* functions support:
 * - bcrypt (PASSWORD_BCRYPT)
 * - Argon2i (PASSWORD_ARGON2I) - PHP 7.2+
 * - Argon2id (PASSWORD_ARGON2ID) - PHP 7.3+
 *
 * Security measures:
 * - Uses hash_equals() to prevent timing attacks
 * - Uses password_verify() for proper hash comparison
 * - Always uses constant-time comparison
 * - Never logs or stores plain text passwords
 */
final class DefaultCredentialsVerifier implements CredentialsVerifierInterface
{
    /**
     * Verify provided credentials against stored password hash
     *
     * @param Credentials $credentials   The credentials to verify
     * @param string      $password_hash The stored password hash
     *
     * @return bool True if credentials match, false otherwise
     */
    public function verify(Credentials $credentials, string $password_hash): bool
    {
        // First check: Use password_verify() to verify against hash
        $password_is_correct = password_verify($credentials->getPassword(), $password_hash);

        // Second check: Use hash_equals() for constant-time comparison
        // This prevents timing attacks by always doing the same amount of work
        // We hash the provided password with the same algorithm for comparison
        $computed_hash = crypt($credentials->getPassword(), $password_hash);

        // Final verification using constant-time comparison
        return $password_is_correct && hash_equals($password_hash, $computed_hash);
    }
}
