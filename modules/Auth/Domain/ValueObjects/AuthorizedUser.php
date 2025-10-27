<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Domain\ValueObjects;

/**
 * AuthorizedUser ValueObject
 *
 * Represents an authenticated and authorized user with all their
 * session data, roles, and permissions.
 *
 * This object is immutable and used throughout request processing
 * to provide consistent user information.
 */
final class AuthorizedUser
{
    /** @var int|string */
    private $user_id;
    private string $username;
    /** @var array<string> */
    private array $roles;
    /** @var array<string> */
    private array $permissions;
    /** @var array<string, mixed> */
    private array $user_data;

    /**
     * @param int|string $user_id      Unique user identifier
     * @param string     $username     Username
     * @param array      $roles        User roles (empty array if none)
     * @param array      $permissions  User permissions (empty array if none)
     * @param array      $user_data    Additional user data
     */
    public function __construct(
        $user_id,
        string $username,
        array $roles = [],
        array $permissions = [],
        array $user_data = []
    ) {
        if (empty($username)) {
            throw new \InvalidArgumentException('Username cannot be empty');
        }

        $this->user_id = $user_id;
        $this->username = $username;
        $this->roles = $roles;
        $this->permissions = $permissions;
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

    /**
     * @return array<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @return array<string>
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserData(): array
    {
        return $this->user_data;
    }

    /**
     * Check if user has a specific role
     *
     * @param string $role Role name
     *
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->roles, true);
    }

    /**
     * Check if user has a specific permission
     *
     * @param string $permission Permission name
     *
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return \in_array($permission, $this->permissions, true);
    }

    /**
     * Check if user has any of the provided roles
     *
     * @param array $roles Roles to check
     *
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the provided permissions
     *
     * @param array $permissions Permissions to check
     *
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get user data value by key
     *
     * @param string $key     Data key
     * @param mixed  $default Default if not found
     *
     * @return mixed
     */
    public function getUserDataValue(string $key, $default = null)
    {
        return $this->user_data[$key] ?? $default;
    }
}
