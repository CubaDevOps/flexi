<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects;

/**
 * AuthenticationResult ValueObject
 *
 * Represents the result of a successful authentication.
 * Contains all the information needed to establish a user session.
 */
final class AuthenticationResult
{
    /** @var int|string */
    private $user_id;
    private string $username;
    private array $user_data;

    /**
     * @param int|string $user_id   Unique user identifier
     * @param string     $username  Username of authenticated user
     * @param array      $user_data Additional user data (email, name, roles, etc.)
     */
    public function __construct($user_id, string $username, array $user_data = [])
    {
        if (empty($username)) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        $this->user_id = $user_id;
        $this->username = $username;
        $this->user_data = $user_data;
    }

    /**
     * @return int|string
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getUserData(): array
    {
        return $this->user_data;
    }

    /**
     * Get specific user data by key
     *
     * @param string $key     Data key
     * @param mixed  $default Default value if key not found
     *
     * @return mixed
     */
    public function getUserDataValue(string $key, $default = null)
    {
        return $this->user_data[$key] ?? $default;
    }

    /**
     * Get all session data as array
     *
     * @return array
     */
    public function toSessionData(): array
    {
        return [
            'user_id' => $this->user_id,
            'username' => $this->username,
            'authenticated' => true,
            'authenticated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ...$this->user_data,
        ];
    }
}
