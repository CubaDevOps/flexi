<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Adapters;

use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\AuthorizationProviderInterface;

/**
 * RoleBasedAuthorizationProvider - Role-Based Access Control (RBAC)
 *
 * Simple RBAC implementation that checks if user has at least one
 * of the required roles and all required permissions.
 *
 * User data should contain:
 * - 'roles': array of role names
 * - 'permissions': array of permission names
 */
final class RoleBasedAuthorizationProvider implements AuthorizationProviderInterface
{
    public function authorize(
        array $user_data,
        array $required_roles = [],
        array $required_permissions = []
    ): bool {
        // If no roles or permissions required, authorization is granted
        if (empty($required_roles) && empty($required_permissions)) {
            return true;
        }

        // Check roles if required
        if (!empty($required_roles)) {
            if (!$this->hasAnyRole($user_data, $required_roles)) {
                return false;
            }
        }

        // Check permissions if required (must have ALL)
        if (!empty($required_permissions)) {
            if (!$this->hasAllPermissions($user_data, $required_permissions)) {
                return false;
            }
        }

        return true;
    }

    public function hasRole(array $user_data, string $role): bool
    {
        $user_roles = (array) ($user_data['roles'] ?? []);

        return \in_array($role, $user_roles, true);
    }

    public function hasPermission(array $user_data, string $permission): bool
    {
        $user_permissions = (array) ($user_data['permissions'] ?? []);

        return \in_array($permission, $user_permissions, true);
    }

    /**
     * Check if user has at least one of the provided roles
     *
     * @param array $user_data       User data
     * @param array $required_roles  Roles to check
     *
     * @return bool
     */
    private function hasAnyRole(array $user_data, array $required_roles): bool
    {
        foreach ($required_roles as $role) {
            if ($this->hasRole($user_data, $role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has all of the provided permissions
     *
     * @param array $user_data            User data
     * @param array $required_permissions Permissions to check
     *
     * @return bool
     */
    private function hasAllPermissions(array $user_data, array $required_permissions): bool
    {
        foreach ($required_permissions as $permission) {
            if (!$this->hasPermission($user_data, $permission)) {
                return false;
            }
        }

        return true;
    }
}
