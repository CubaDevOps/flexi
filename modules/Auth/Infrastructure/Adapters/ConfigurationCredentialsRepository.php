<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Auth\Infrastructure\Adapters;

use CubaDevOps\Flexi\Modules\Auth\Domain\Interfaces\CredentialsRepositoryInterface;

/**
 * ConfigurationCredentialsRepository - File-based credentials storage
 *
 * Reads credentials directly from modules/Auth/Config/credentials.json
 * This implementation is independent from the framework's global configuration.
 *
 * Security note: This is NOT suitable for production with sensitive data.
 * Use database or secure credential management system in production.
 *
 * For production:
 * - Implement DatabaseCredentialsRepository using your database
 * - Implement LdapCredentialsRepository for LDAP integration
 * - Implement RemoteCredentialsRepository for external auth service
 *
 * Example credentials.json:
 * {
 *   "users": [
 *     {
 *       "username": "admin",
 *       "password_hash": "$2y$10$...",
 *       "user_id": 1,
 *       "full_name": "Administrator",
 *       "email": "admin@example.com"
 *     }
 *   ]
 * }
 */
final class ConfigurationCredentialsRepository implements CredentialsRepositoryInterface
{
    /** @var array<array-key, mixed> */
    private array $cached_users = [];
    private bool $initialized = false;
    private string $credentials_file;

    /**
     * @param string $credentials_file Path to credentials.json file
     *                                 Defaults to modules/Auth/Config/credentials.json
     */
    public function __construct(string $credentials_file = '')
    {
        if (empty($credentials_file)) {
            // Default location: modules/Auth/Config/credentials.json
            $this->credentials_file = \dirname(__DIR__, 2) . '/Config/credentials.json';
        } else {
            $this->credentials_file = $credentials_file;
        }
    }

    /**
     * Find user credentials by username
     *
     * @param string $username Username to search for
     *
     * @return array{username: string, password_hash: string, user_id: int|string}|null
     */
    public function findByUsername(string $username): ?array
    {
        $this->initializeUsers();

        foreach ($this->cached_users as $user) {
            if ($user['username'] === $username) {
                return [
                    'username' => $user['username'],
                    'password_hash' => $user['password_hash'],
                    'user_id' => $user['user_id'],
                    ...array_diff_key($user, array_flip(['username', 'password_hash', 'user_id'])),
                ];
            }
        }

        return null;
    }

    /**
     * Initialize users from JSON file
     *
     * @return void
     *
     * @throws \RuntimeException If credentials file cannot be read
     */
    private function initializeUsers(): void
    {
        if ($this->initialized) {
            return;
        }

        try {
            if (!file_exists($this->credentials_file)) {
                throw new \RuntimeException(sprintf(
                    'Credentials file not found: %s',
                    $this->credentials_file
                ));
            }

            $json_content = file_get_contents($this->credentials_file);

            if (false === $json_content) {
                throw new \RuntimeException(sprintf(
                    'Failed to read credentials file: %s',
                    $this->credentials_file
                ));
            }

            $config = json_decode($json_content, true, 512, JSON_THROW_ON_ERROR);

            if (!\is_array($config) || !\array_key_exists('users', $config)) {
                throw new \RuntimeException('Invalid credentials.json format: missing "users" key');
            }

            if (!\is_array($config['users'])) {
                throw new \RuntimeException('Invalid credentials.json format: "users" must be an array');
            }

            $this->cached_users = $config['users'];
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf(
                'Failed to parse credentials.json: %s',
                $e->getMessage()
            ));
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new \RuntimeException(sprintf(
                'Unexpected error reading credentials: %s',
                $e->getMessage()
            ));
        }

        $this->initialized = true;
    }
}
