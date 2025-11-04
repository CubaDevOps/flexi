<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects;

/**
 * Credentials ValueObject
 *
 * Represents a pair of username and password (plain text).
 * This value object is immutable and used during authentication process.
 *
 * Security: This object should only be used temporarily during authentication.
 * The plain text password is never stored or persisted, only compared against hashes.
 */
final class Credentials
{
    private string $username;
    private string $password;

    public function __construct(string $username, string $password)
    {
        if (empty($username)) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        if (empty($password)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }

        $this->username = $username;
        $this->password = $password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * String representation (does NOT expose password)
     *
     * @return string
     */
    public function __toString(): string
    {
        return sprintf('Credentials(username=%s)', $this->username);
    }
}
