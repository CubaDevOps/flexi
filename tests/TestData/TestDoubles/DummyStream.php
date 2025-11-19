<?php

declare(strict_types=1);

namespace Flexi\Test\TestData\TestDoubles;

use Psr\Http\Message\StreamInterface;

/**
 * Dummy PSR-7 Stream implementation for testing.
 * Minimal implementation of StreamInterface without external dependencies.
 * Stores content in memory and supports basic read/write operations.
 */
class DummyStream implements StreamInterface
{
    private string $content = '';
    private int $position = 0;
    private bool $readable = true;
    private bool $writable = true;
    private bool $seekable = true;

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    public function __toString(): string
    {
        return $this->content;
    }

    public function close(): void
    {
        $this->readable = false;
        $this->writable = false;
    }

    public function detach()
    {
        return null;
    }

    public function getSize(): ?int
    {
        return strlen($this->content);
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new \RuntimeException('Stream is not seekable');
        }

        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = strlen($this->content) + $offset;
                break;
            default:
                throw new \InvalidArgumentException('Invalid whence value');
        }

        if ($this->position < 0) {
            $this->position = 0;
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write($string): int
    {
        if (!$this->writable) {
            throw new \RuntimeException('Stream is not writable');
        }

        $length = strlen($string);
        $this->content .= $string;
        $this->position += $length;
        return $length;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read($length): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('Stream is not readable');
        }

        $data = substr($this->content, $this->position, $length);
        $this->position += strlen($data);
        return $data;
    }

    public function getContents(): string
    {
        if (!$this->readable) {
            throw new \RuntimeException('Stream is not readable');
        }

        $data = substr($this->content, $this->position);
        $this->position = strlen($this->content);
        return $data;
    }

    public function getMetadata($key = null)
    {
        $metadata = [
            'readable' => $this->readable,
            'writable' => $this->writable,
            'seekable' => $this->seekable,
        ];

        if ($key === null) {
            return $metadata;
        }

        return $metadata[$key] ?? null;
    }
}
