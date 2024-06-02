<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;

/**
 * @template TKey
 * @template TValue
 *
 * @implements SessionStorageInterface<TKey,TValue>
 */
class NativeSessionStorage implements SessionStorageInterface
{
    private array $storage;

    public function __construct(array $options = [])
    {
        if ((PHP_SESSION_NONE === session_status()) && !session_start($options)) {
            throw new \RuntimeException('Failed to start the session.');
        }

        $this->storage = &$_SESSION;
    }

    public function set(string $key, $value): void
    {
        $this->storage[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return $this->storage[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    public function remove(string $key): void
    {
        unset($this->storage[$key]);
    }

    public function clear(): void
    {
        session_unset();
    }

    public function all(): array
    {
        return $this->storage;
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
