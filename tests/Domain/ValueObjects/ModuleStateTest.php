<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Domain\ValueObjects;

use CubaDevOps\Flexi\Domain\ValueObjects\ModuleState;
use CubaDevOps\Flexi\Domain\ValueObjects\ModuleType;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class ModuleStateTest extends TestCase
{
    public function testConstructorAndAccessorsExposeValues(): void
    {
        $lastModified = new DateTimeImmutable('2025-01-01T12:00:00+00:00');
        $metadata = ['source' => 'sync'];
        $state = new ModuleState(
            'analytics',
            true,
            ModuleType::vendor(),
            $lastModified,
            'tester',
            $metadata
        );

        $this->assertSame('analytics', $state->getModuleName());
        $this->assertTrue($state->isActive());
        $this->assertSame('vendor', $state->getType()->getValue());
        $this->assertSame($lastModified, $state->getLastModified());
        $this->assertSame('tester', $state->getModifiedBy());
        $this->assertSame($metadata, $state->getMetadata());
        $this->assertSame('sync', $state->getMetadataValue('source'));
        $this->assertSame('default', $state->getMetadataValue('missing', 'default'));
    }

    public function testActivateCreatesNewActiveState(): void
    {
        $state = new ModuleState('blog', false, ModuleType::local(), new DateTimeImmutable('2025-01-01T00:00:00+00:00'), 'tester');
        $activated = $state->activate('automation');

        $this->assertNotSame($state, $activated);
        $this->assertFalse($state->isActive());
        $this->assertTrue($activated->isActive());
        $this->assertSame('automation', $activated->getModifiedBy());
        $this->assertNotEquals($state->getLastModified(), $activated->getLastModified());
    }

    public function testDeactivateCreatesNewInactiveState(): void
    {
        $state = new ModuleState('blog', true, ModuleType::local(), new DateTimeImmutable('2025-01-01T00:00:00+00:00'), 'tester');
        $deactivated = $state->deactivate();

        $this->assertNotSame($state, $deactivated);
        $this->assertTrue($state->isActive());
        $this->assertFalse($deactivated->isActive());
        $this->assertSame('tester', $deactivated->getModifiedBy());
    }

    public function testWithTypeAndWithMetadataReturnUpdatedState(): void
    {
        $state = new ModuleState('blog', true, ModuleType::local(), new DateTimeImmutable('2025-01-01T00:00:00+00:00'), 'tester', ['initial' => true]);

        $withType = $state->withType(ModuleType::vendor(), 'ci');
        $this->assertSame('vendor', $withType->getType()->getValue());
        $this->assertSame('ci', $withType->getModifiedBy());
        $this->assertNotEquals($state->getLastModified(), $withType->getLastModified());

        $withMetadata = $state->withMetadata(['initial' => false, 'owner' => 'team'], 'ops');
        $this->assertSame(['initial' => false, 'owner' => 'team'], $withMetadata->getMetadata());
        $this->assertSame('ops', $withMetadata->getModifiedBy());
    }

    public function testToArrayExportsSerializableRepresentation(): void
    {
        $lastModified = new DateTimeImmutable('2025-01-01T12:00:00+00:00');
        $state = new ModuleState('analytics', true, ModuleType::mixed(), $lastModified, 'system', ['key' => 'value']);

        $array = $state->toArray();
        $this->assertSame([
            'active' => true,
            'type' => 'mixed',
            'lastModified' => $lastModified->format(DateTimeImmutable::ATOM),
            'modifiedBy' => 'system',
            'metadata' => ['key' => 'value'],
        ], $array);
    }

    public function testFromArrayRestoresState(): void
    {
        $data = [
            'active' => false,
            'type' => 'vendor',
            'lastModified' => '2025-01-02T00:00:00+00:00',
            'modifiedBy' => 'tester',
            'metadata' => ['flag' => true],
        ];

        $state = ModuleState::fromArray('catalog', $data);

        $this->assertSame('catalog', $state->getModuleName());
        $this->assertFalse($state->isActive());
        $this->assertSame('vendor', $state->getType()->getValue());
        $this->assertSame('tester', $state->getModifiedBy());
        $this->assertSame(['flag' => true], $state->getMetadata());
        $this->assertSame('2025-01-02T00:00:00+00:00', $state->getLastModified()->format(DateTimeImmutable::ATOM));
    }

    public function testCreateActiveAndCreateInactiveProvideDefaults(): void
    {
        $active = ModuleState::createActive('catalog', ModuleType::vendor());
        $inactive = ModuleState::createInactive('catalog');

        $this->assertTrue($active->isActive());
        $this->assertSame('vendor', $active->getType()->getValue());
        $this->assertSame('system', $active->getModifiedBy());

        $this->assertFalse($inactive->isActive());
        $this->assertSame('local', $inactive->getType()->getValue());
        $this->assertSame('system', $inactive->getModifiedBy());
    }
}
