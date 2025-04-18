<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventInterface;

class Event implements EventInterface
{
    private string $name;
    private array $data;
    private \DateTimeImmutable $occurredOn;
    private string $fired_by;
    private bool $is_stopped = false;

    public function __construct(string $name, string $fired_by, array $data = [])
    {
        self::assertRequiredFields(['event' => $name, 'fired_by' => $fired_by, 'data' => $data]);
        $this->name = $name;
        $this->data = $data;
        $this->fired_by = $fired_by;
        $this->occurredOn = new \DateTimeImmutable();
    }

    /**
     * @return self
     */
    public static function fromArray(array $data): DTOInterface
    {
        self::assertRequiredFields($data);

        return new self($data['event'], $data['fired_by'], $data['data'] ?? []);
    }

    public static function validate(array $data): bool
    {
        return !empty($data['event']) && !empty($data['fired_by']);
    }

    /**
     * @param array $data
     * @return void
     */
    private static function assertRequiredFields(array $data): void
    {
        if (!self::validate($data)) {
            throw new \InvalidArgumentException('Invalid parameters provided for ' . self::class);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @throws \JsonException
     */
    public function serialize(): string
    {
        //Todo refactor to use JsonHandler Trait, implement serialize and deserialize methods for json
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @return (array|string)[]
     *
     * @psalm-return array{event: string, data: array, occurred_on: string}
     */
    public function toArray(): array
    {
        return [
            'event' => $this->name,
            'data' => $this->data,
            'fired_by' => $this->fired_by,
            'occurred_on' => $this->occurredOn()->format(DATE_ATOM),
        ];
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function firedBy(): string
    {
        return $this->fired_by;
    }

    public function get(string $name)
    {
        return $this->toArray()[$name] ?? null;
    }

    /**
     * @return bool
     */
    public function isPropagationStopped(): bool
    {
        return $this->is_stopped;
    }

    public function stopPropagation(): void
    {
        $this->is_stopped = true;
    }
}
