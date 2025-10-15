<?php

namespace CubaDevOps\Flexi\Test\Infrastructure\Ui;

use CubaDevOps\Flexi\Infrastructure\Ui\HtmlRender;
use CubaDevOps\Flexi\Infrastructure\Ui\Template;
use PHPUnit\Framework\TestCase;

class HtmlRenderTest extends TestCase
{
    private const TEMPLATE_CONTENT = '<test>{{1-template-data}}{{2-template-data}}</test>';
    private const RENDER_VARS = [
        '1-template-data' => 1,
        '2-template-data' => '2 $'
    ];

    private Template $template;
    private HtmlRender $htmlRender;

    public function setUp(): void
    {
        $this->template = $this->createMock(Template::class);
        $this->htmlRender = new HtmlRender();
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
}
