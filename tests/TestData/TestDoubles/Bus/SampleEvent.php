<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\TestData\TestDoubles\Bus;

use Flexi\Contracts\Interfaces\EventInterface;

final class SampleEvent implements EventInterface
{
    private string $name;

    /** @var array<string, mixed> */
    private array $data;

    private bool $stopped = false;

    private \DateTimeImmutable $occurredOn;

    public function __construct(string $name, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
        $this->occurredOn = new \DateTimeImmutable('@0');
    }

    public static function fromArray(array $data): self
    {
        return new self($data['name'] ?? 'sample', $data);
    }

    public static function validate(array $data): bool
    {
        return true;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    public function get(string $name)
    {
        return $this->data[$name] ?? null;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function firedBy(): string
    {
        return 'tests';
    }

    public function serialize(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function stopPropagation(): void
    {
        $this->stopped = true;
    }

    public function isPropagationStopped(): bool
    {
        return $this->stopped;
    }
}
