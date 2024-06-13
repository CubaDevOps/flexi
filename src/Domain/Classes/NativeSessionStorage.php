<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * @template TKey
 * @template TValue
 *
 * @implements SessionStorageInterface<TKey,TValue>
 */
class NativeSessionStorage implements SessionStorageInterface
{
    private array $storage;
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger, array $options = [])
    {
        $this->logger = $logger;

        if (session_status() === PHP_SESSION_NONE && !$this->headersSent() && !session_start($options)) {
            $message = 'Failed to start the session.';
            $this->logger->error($message, [__CLASS__]);
            throw new \RuntimeException($message);
        }

        $this->storage = &$_SESSION;
    }

    private function headersSent(): bool
    {
        if (headers_sent($file, $line)) {
            $this->logger->error(
                sprintf('Headers already sent in %s on line %s', $file, $line),
                [__CLASS__]
            );
            return true;
        }
        return false;
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

    public function has(string $key): bool
    {
        return isset($this->storage[$key]);
    }

    /**
     * @param string $offset
     *
     * @return TValue
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function get(string $key, $default = null)
    {
        return $this->storage[$key] ?? $default;
    }

    /**
     * @param string $offset
     * @param TValue $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function set(string $key, $value): void
    {
        $this->storage[$key] = $value;
    }

    /**
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    public function remove(string $key): void
    {
        unset($this->storage[$key]);
    }
}
