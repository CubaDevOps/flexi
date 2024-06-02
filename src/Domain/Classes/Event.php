<?php

namespace CubaDevOps\Flexi\Domain\Classes;

use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\EventInterface;
use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;

class Event implements EventInterface
{
    private string $name;
    private array $data;
    private DateTimeImmutable $occurredOn;
    private string $fired_by;

    public function __construct(string $name, string $fired_by, array $data = [])
    {
        $this->name = $name;
        $this->data = $data;
        $this->fired_by = $fired_by;
        $this->occurredOn = new DateTimeImmutable();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function occurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
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

    /**
     * @throws JsonException
     */
    public function serialize(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    /**
     * @return string
     */
    public function firedBy(): string
    {
       return $this->fired_by;
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

        return new self($data['event'], $data['fired_by'], $data['data'] ?? []);
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function validate(array $data): bool
    {
        return isset($data['event'], $data['fired_by']);
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->toArray()[$name];
    }
}
