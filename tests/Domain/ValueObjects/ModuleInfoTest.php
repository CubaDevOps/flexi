<?php

declare(strict_types=1);

namespace Flexi\Test\Domain\ValueObjects;

use Flexi\Domain\ValueObjects\ModuleInfo;
use Flexi\Domain\ValueObjects\ModuleType;
use PHPUnit\Framework\TestCase;

class ModuleInfoTest extends TestCase
{
    public function testAccessorsExposeConstructorValues(): void
    {
        $type = ModuleType::vendor();
        $info = new ModuleInfo(
            'analytics',
            'flexi/module-analytics',
            $type,
            '/modules/analytics',
            '1.2.3',
            true,
            ['author' => 'Flexi Team']
        );

        $this->assertSame('analytics', $info->getName());
        $this->assertSame('flexi/module-analytics', $info->getPackage());
        $this->assertSame($type, $info->getType());
        $this->assertSame('/modules/analytics', $info->getPath());
        $this->assertSame('1.2.3', $info->getVersion());
        $this->assertTrue($info->isActive());
        $this->assertSame(['author' => 'Flexi Team'], $info->getMetadata());
        $this->assertSame('Flexi Team', $info->getMetadataValue('author'));
        $this->assertSame('unknown', $info->getMetadataValue('category', 'unknown'));
    }

    public function testWithActivationStatusProducesNewInstance(): void
    {
        $info = new ModuleInfo('blog', 'flexi/blog', ModuleType::local(), '/modules/blog');
        $activated = $info->withActivationStatus(true);

        $this->assertNotSame($info, $activated);
        $this->assertFalse($info->isActive());
        $this->assertTrue($activated->isActive());
    }

    public function testWithMetadataMergesData(): void
    {
        $info = new ModuleInfo('blog', 'flexi/blog', ModuleType::local(), '/modules/blog', null, false, ['role' => 'core']);
        $updated = $info->withMetadata(['owner' => 'team', 'role' => 'extension']);

        $this->assertSame(['role' => 'extension', 'owner' => 'team'], $updated->getMetadata());
        $this->assertSame('core', $info->getMetadataValue('role'));
    }

    public function testWithTypeAndWithPathUpdateValues(): void
    {
        $info = new ModuleInfo('blog', 'flexi/blog', ModuleType::local(), '/modules/blog');
        $withType = $info->withType(ModuleType::vendor());
        $withPath = $info->withPath('/vendor/flexi/blog');

        $this->assertSame('vendor', $withType->getType()->getValue());
        $this->assertSame('/vendor/flexi/blog', $withPath->getPath());
    }

    public function testConflictDevelopmentAndPackagedFlagsDelegateToType(): void
    {
        $mixedInfo = new ModuleInfo('blog', 'flexi/blog', ModuleType::mixed(), '/modules/blog');
        $localInfo = new ModuleInfo('blog', 'flexi/blog', ModuleType::local(), '/modules/blog');
        $vendorInfo = new ModuleInfo('blog', 'flexi/blog', ModuleType::vendor(), '/vendor/flexi/blog');

        $this->assertTrue($mixedInfo->hasConflict());
        $this->assertTrue($mixedInfo->isDevelopment());
        $this->assertTrue($mixedInfo->isPackaged());

        $this->assertTrue($localInfo->isDevelopment());
        $this->assertFalse($localInfo->isPackaged());

        $this->assertFalse($vendorInfo->isDevelopment());
        $this->assertTrue($vendorInfo->isPackaged());
    }

    public function testToArrayAndFromArrayRoundTrip(): void
    {
        $info = new ModuleInfo(
            'analytics',
            'flexi/analytics',
            ModuleType::mixed(),
            '/modules/analytics',
            '2.0.0',
            true,
            ['source' => 'hybrid']
        );

        $array = $info->toArray();
        $this->assertSame([
            'name' => 'analytics',
            'package' => 'flexi/analytics',
            'type' => 'mixed',
            'path' => '/modules/analytics',
            'version' => '2.0.0',
            'isActive' => true,
            'metadata' => ['source' => 'hybrid'],
        ], $array);

        $restored = ModuleInfo::fromArray($array);
        $this->assertSame('analytics', $restored->getName());
        $this->assertSame('mixed', $restored->getType()->getValue());
        $this->assertTrue($restored->isActive());
        $this->assertSame('hybrid', $restored->getMetadataValue('source'));
    }

    public function testFromArrayAppliesDefaultsWhenFieldsMissing(): void
    {
        $info = ModuleInfo::fromArray([]);

        $this->assertSame('', $info->getName());
        $this->assertSame('', $info->getPackage());
        $this->assertSame('', $info->getPath());
        $this->assertNull($info->getVersion());
        $this->assertFalse($info->isActive());
        $this->assertSame('local', $info->getType()->getValue());
    }
}
