<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces;

/**
 * AuthorizationProviderInterface - Agnostic authorization verification
 *
 * This interface defines how to verify if a user has the required
 * authorization to access a resource. Implementations can support
 * different authorization strategies:
 *
 * - Role-Based Access Control (RBAC)
 * - Permission-Based Access Control (PBAC)
 * - Policy-Based Access Control
 * - Custom authorization logic
 */
interface AuthorizationProviderInterface
{
    /**
     * Check if user is authorized based on roles and/or permissions
     *
     * @param array  $user_data              User data from session/credentials
     * @param array  $required_roles         Required roles (user must have at least one)
     * @param array  $required_permissions   Required permissions (user must have all)
     *
     * @return bool True if authorized, false otherwise
     */
    public function authorize(
        array $user_data,
        array $required_roles = [],
        array $required_permissions = []
    ): bool;

    /**
     * Check if user has a specific role
     *
     * @param array  $user_data User data from session/credentials
     * @param string $role      Role to check
     *
     * @return bool True if user has role, false otherwise
     */
    public function hasRole(array $user_data, string $role): bool;

    /**
     * Check if user has a specific permission
     *
     * @param array  $user_data   User data from session/credentials
     * @param string $permission  Permission to check
     *
     * @return bool True if user has permission, false otherwise
     */
    public function hasPermission(array $user_data, string $permission): bool;
}
