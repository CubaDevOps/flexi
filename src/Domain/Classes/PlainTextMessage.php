<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use DateTimeImmutable;
use InvalidArgumentException;

class PlainTextMessage implements MessageInterface
{
    private string $body;
    private DateTimeImmutable $created_at;

    /**
     * PlainTextMessage constructor.
     */
    public function __construct(string $body)
    {
        $this->body = $body;
        $this->created_at = new DateTimeImmutable();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->created_at;
    }

    public function __toString(): string
    {
        return $this->body;
    }

    /**
     * @return (DateTimeImmutable|string)[]
     *
     * @psalm-return array{body: string, created_at: DateTimeImmutable}
     */
    public function toArray(): array
    {
        return [
            'body' => $this->body,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): DTOInterface
    {
        if (!self::validate($data)) {
            throw new InvalidArgumentException('Invalid data provided for ' . self::class);
        }
           return new self($data['body']);
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function validate(array $data): bool
    {
        return isset($data['body']);
    }

    /**
     * @param string $name
     * @return DateTimeImmutable|string
     */
    public function get(string $name)
    {
        return $this->toArray()[$name];
    }
}
