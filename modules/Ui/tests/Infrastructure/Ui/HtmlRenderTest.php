<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Test\Modules\Ui\Infrastructure\Ui;

use CubaDevOps\Flexi\Contracts\Interfaces\TemplateInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\TemplateLocatorInterface;
use CubaDevOps\Flexi\Modules\Ui\Infrastructure\Ui\HtmlRender;
use PHPUnit\Framework\TestCase;

class HtmlRenderTest extends TestCase
{
    private const TEMPLATE_CONTENT = '<test>{{1-template-data}}{{2-template-data}}</test>';
    private const RENDER_VARS = [
        '1-template-data' => 1,
        '2-template-data' => '2 $',
    ];

    private $template;
    private $templateLocator;
    private HtmlRender $htmlRender;

    public function setUp(): void
    {
        $this->template = $this->createMock(TemplateInterface::class);
        $this->templateLocator = $this->createMock(TemplateLocatorInterface::class);
        $this->htmlRender = new HtmlRender($this->templateLocator);
    }

    public function testRender(): void
    {
        $expected = '<test>12 $</test>';

        $this->template
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(self::TEMPLATE_CONTENT);

        $rendered = $this->htmlRender->render($this->template, self::RENDER_VARS);

        $this->assertNotEmpty($rendered);
        $this->assertEquals($expected, $rendered);
    }

    public function testRenderWithStringPath(): void
    {
        $expected = '<test>12 $</test>';
        $templatePath = '/path/to/template.html';

        $this->template
            ->expects($this->once())
            ->method('getContent')
            ->willReturn(self::TEMPLATE_CONTENT);

        $this->templateLocator
            ->expects($this->once())
            ->method('locate')
            ->with($templatePath)
            ->willReturn($this->template);

        $rendered = $this->htmlRender->render($templatePath, self::RENDER_VARS);

        $this->assertNotEmpty($rendered);
        $this->assertEquals($expected, $rendered);
    }
}
