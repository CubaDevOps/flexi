<?php

namespace CubaDevOps\Flexi\Test\Domain\Classes;

use CubaDevOps\Flexi\Domain\Classes\Template;
use CubaDevOps\Flexi\Test\TestData\TestDoubles\FileHandler;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{

    private Template $template;
    private string $path;
    private FileHandler $file_handler;

    public function __construct()
    {
        parent::__construct();
        $this->file_handler = new FileHandler();
    }

    public function setUp(): void
    {
        $this->path = './src/Infrastructure/Ui/Templates/404.html';

        $this->template = new Template($this->path);
    }

    public function testInvalidPath(): void
    {
        $path = './var/invalid/path/test/file.html';
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template file not found: ' . $this->file_handler->normalize($path));

        new Template($path);
    }

    public function testGetContent(): void
    {
        $content = $this->template->getContent();
        $this->assertNotEmpty($content);
    }

    public function testGetTemplatePath(): void
    {
        $this->assertEquals($this->file_handler->normalize($this->path), $this->template->getTemplatePath());
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
