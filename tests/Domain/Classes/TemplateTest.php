<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\Template;
use CubaDevOps\Flexi\Domain\Utils\FileHandlerTrait;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    use FileHandlerTrait;

    private Template $template;
    private string $path;

    public function setUp(): void
    {
        $this->path = './src/Infrastructure/Ui/Templates/404.html';

        $this->template = new Template($this->path);
    }

    public function testInvalidPath(): void
    {
        $path = './var/invalid/path/test/file.html';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template file not found: ' . $this->normalize($path));

        new Template($path);
    }

    public function testGetContent(): void
    {
        $content = $this->template->getContent();
        $this->assertNotEmpty($content);
    }

    public function testGetTemplatePath(): void
    {
        $this->assertEquals($this->normalize($this->path), $this->template->getTemplatePath());
    }

    public function testGetTemplateName(): void
    {
        $this->assertEquals('404.html', $this->template->getTemplateName());
    }

    public function testGetTemplateExtension(): void
    {
        $this->assertEquals('html', $this->template->getTemplateExtension());
    }
}
