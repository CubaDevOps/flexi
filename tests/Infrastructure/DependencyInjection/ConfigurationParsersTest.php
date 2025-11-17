<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Tests\Infrastructure\DependencyInjection;

use CubaDevOps\Flexi\Infrastructure\DependencyInjection\RoutesDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\HandlersDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\DependencyInjection\ListenersDefinitionParser;
use CubaDevOps\Flexi\Infrastructure\Classes\InMemoryCache;
use PHPUnit\Framework\TestCase;

class ConfigurationParsersTest extends TestCase
{
    private InMemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new InMemoryCache();
    }

    public function testRoutesDefinitionParserCanBeCreated(): void
    {
        $parser = new RoutesDefinitionParser($this->cache);

        $this->assertInstanceOf(RoutesDefinitionParser::class, $parser);
    }

    public function testHandlersDefinitionParserCanBeCreated(): void
    {
        $parser = new HandlersDefinitionParser($this->cache);

        $this->assertInstanceOf(HandlersDefinitionParser::class, $parser);
    }

    public function testListenersDefinitionParserCanBeCreated(): void
    {
        $parser = new ListenersDefinitionParser($this->cache);

        $this->assertInstanceOf(ListenersDefinitionParser::class, $parser);
    }

    public function testHandlersDefinitionParserThrowsExceptionForMissingFile(): void
    {
        $parser = new HandlersDefinitionParser($this->cache);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Handlers file not found: /non/existent/file.json');

        $parser->parse('/non/existent/file.json');
    }

    public function testListenersDefinitionParserThrowsExceptionForMissingFile(): void
    {
        $parser = new ListenersDefinitionParser($this->cache);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Listeners file not found: /non/existent/file.json');

        $parser->parse('/non/existent/file.json');
    }

    public function testRoutesDefinitionParserThrowsExceptionForMissingFile(): void
    {
        $parser = new RoutesDefinitionParser($this->cache);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Routes file not found: /non/existent/file.json');

        $parser->parse('/non/existent/file.json');
    }
}