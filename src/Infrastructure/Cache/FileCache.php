<?php

namespace CubaDevOps\Flexi\Infrastructure\Cache;

use CubaDevOps\Flexi\Contracts\CacheContract;
use DateInterval;
use DateTime;
use FilesystemIterator;
use Psr\Cache\InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileCache implements CacheContract
{
    /**
     * @var string The cache directory where files will be stored
     */
    private string $directory;

    /**
     * @var string Default file extension for cache files
     */
    private string $extension = '.cache';

    /**
     * @var int Default file permissions
     */
    private int $filePermission = 0666;

    /**
     * @var int Default directory permissions
     */
    private int $directoryPermission = 0777;

    /**
     * Clear the entire cache.
     *
     * @return bool True on success, false on failure
     */
    public function clear(): bool
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $path) {
            if ($path->isFile() && $path->getExtension() === substr($this->extension, 1)) {
                @unlink($path->getPathname());
            } elseif ($path->isDir() && $path->getPathname() !== $this->directory) {
                @rmdir($path->getPathname());
            }
        }

        return true;
    }

    /**
     * Get multiple cache items.
     *
     * @param iterable $keys The array of keys
     * @param mixed $default Default value for keys that do not exist
     * @return iterable A list of key => value pairs
     */
    public function getMultiple($keys, $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * Get an item from the cache.
     *
     * @param string $key The cache item key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed The cached value or $default
     */
    public function get($key, $default = null)
    {
        $this->validateKey($key);

        $filepath = $this->getFilePath($key);
        if (!file_exists($filepath)) {
            return $default;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return $default;
        }

        $data = $this->unserialize($content);
        if (!isset($data['expiry'], $data['data']) || !is_array($data)) {
            return $default;
        }

        // Check expiration
        if ($data['expiry'] !== 0 && time() >= $data['expiry']) {
            $this->delete($key);
            return $default;
        }

        return $data['data'];
    }

    /**
     * Validate a cache key according to PSR-16 standards.
     *
     * @param string $key The cache key to validate
     */
    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new class extends \InvalidArgumentException implements InvalidArgumentException {
            };
        }

        // PSR-16 prohibits characters: {}()/\@:
        if (preg_match('/[{}()\/\\\\@:]/', $key)) {
            throw new class extends \InvalidArgumentException implements InvalidArgumentException {
                public function __construct()
                {
                    parent::__construct(
                        'Cache key contains invalid characters: {}()/\@:'
                    );
                }
            };
        }
    }

    /**
     * Get the cache file path for a key.
     *
     * @param string $key The cache key
     * @return string The file path
     */
    private function getFilePath(string $key): string
    {
        // Use a hash to avoid filesystem issues with special characters
        $hash = sha1($key);

        // Create a directory structure based on the first two characters of the hash
        // This helps to avoid too many files in a single directory
        $directory = $this->directory . substr($hash, 0, 2) . DIRECTORY_SEPARATOR;

        return $directory . $hash . $this->extension;
    }

    /**
     * Unserialize data from storage.
     *
     * @param string $data The data to unserialize
     * @return mixed The unserialized data
     */
    private function unserialize(string $data)
    {
        try {
            return unserialize($data, ['allowed_classes' => true]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Delete an item from the cache.
     *
     * @param string $key The cache item key
     * @return bool True if the item was successfully removed, false otherwise
     */
    public function delete($key): bool
    {
        $this->validateKey($key);

        $filepath = $this->getFilePath($key);
        if (file_exists($filepath)) {
            return @unlink($filepath);
        }

        return false;
    }

    /**
     * FileCache constructor.
     *
     * @param string $directory The directory where cache files will be stored
     * @throws \RuntimeException If the directory cannot be created or is not writable
     */
    public function __construct(string $directory)
    {
        $this->directory = rtrim($directory, '/\\') . DIRECTORY_SEPARATOR;

        if (!is_dir($this->directory)) {
            $this->createDirectory($this->directory);
        }

        if (!is_writable($this->directory)) {
            throw new \RuntimeException(sprintf('Cache directory "%s" is not writable', $this->directory));
        }
    }

    /**
     * Create a directory with proper permissions.
     *
     * @param string $directory The directory path
     * @throws \RuntimeException If the directory cannot be created
     */
    private function createDirectory(string $directory): void
    {
        $result = @mkdir($directory, $this->directoryPermission, true);

        if (!$result) {
            throw new \RuntimeException(sprintf('Failed to create cache directory "%s"', $directory));
        }
    }

    /**
     * Set multiple cache items.
     *
     * @param iterable $values A list of key => value pairs to cache
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item
     * @return bool True on success, false on failure
     */
    public function setMultiple($values, $ttl = null): bool
    {
        $success = true;

        foreach ($values as $key => $value) {
            $success = $this->set($key, $value, $ttl) && $success;
        }

        return $success;
    }

    /**
     * Set an item in the cache.
     *
     * @param string $key The cache item key
     * @param mixed $value The value to store
     * @param null|int|DateInterval $ttl Optional. The TTL value of this item
     * @return bool True on success, false on failure
     */
    public function set($key,$value, $ttl = null): bool
    {
        $this->validateKey($key);

        $expiry = $this->convertTtlToTimestamp($ttl);
        $data = [
            'expiry' => $expiry,
            'data' => $value
        ];

        $filepath = $this->getFilePath($key);
        $directory = dirname($filepath);

        if (!is_dir($directory)) {
            $this->createDirectory($directory);
        }

        $result = file_put_contents(
            $filepath,
            $this->serialize($data),
            LOCK_EX
        );

        if ($result !== false) {
            chmod($filepath, $this->filePermission);
            return true;
        }

        return false;
    }

    /**
     * Convert a TTL value to a timestamp.
     *
     * @param null|int|DateInterval $ttl The TTL value
     * @return int The timestamp when the cache will expire, or 0 for unlimited
     */
    private function convertTtlToTimestamp($ttl = null): int
    {
        // Null TTL means no expiration
        if ($ttl === null) {
            return 0;
        }

        // Handles DateInterval objects
        if ($ttl instanceof \DateInterval) {
            $dateTime = new DateTime();
            $dateTime->add($ttl);
            return $dateTime->getTimestamp();
        }

        // Handle integers (seconds)
        if (is_int($ttl)) {
            // Negative or zero TTL means the item should be expired immediately
            if ($ttl <= 0) {
                return 1; // Use 1 instead of time() to ensure it's expired
            }

            return time() + $ttl;
        }

        // Default fallback, though this shouldn't be reached with proper type hinting
        return 0;
    }

    /**
     * Serialize data for storage.
     *
     * @param mixed $data The data to serialize
     * @return string The serialized data
     */
    private function serialize($data): string
    {
        return serialize($data);
    }

    /**
     * Delete multiple cache items.
     *
     * @param iterable $keys The array of keys to delete
     * @return bool True on success, false on failure
     */
    public function deleteMultiple($keys): bool
    {
        $success = true;

        foreach ($keys as $key) {
            $success = $this->delete($key) && $success;
        }

        return $success;
    }

    /**
     * Check if item exists in the cache.
     *
     * @param string $key The cache item key
     * @return bool True if the key exists, false otherwise
     */
    public function has($key): bool
    {
        $this->validateKey($key);

        $filepath = $this->getFilePath($key);
        if (!file_exists($filepath)) {
            return false;
        }

        $content = file_get_contents($filepath);
        if ($content === false) {
            return false;
        }

        $data = $this->unserialize($content);
        if (!isset($data['expiry'], $data['data']) || !is_array($data)) {
            return false;
        }

        // Check expiration
        if ($data['expiry'] !== 0 && time() >= $data['expiry']) {
            $this->delete($key);
            return false;
        }

        return true;
    }
}