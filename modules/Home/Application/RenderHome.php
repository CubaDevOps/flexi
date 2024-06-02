<?php

namespace CubaDevOps\Flexi\Modules\Home\Application;

use CubaDevOps\Flexi\Domain\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Domain\Classes\Template;
use CubaDevOps\Flexi\Domain\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Domain\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Domain\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Domain\Interfaces\TemplateEngineInterface;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;

class RenderHome implements HandlerInterface
{
    private TemplateEngineInterface $html_render;

    public function __construct(TemplateEngineInterface $html_render)
    {
        $this->html_render = $html_render;
    }

    /**
     * @param HomePageDTO $dto
     */
    public function handle(DTOInterface $dto): MessageInterface
    {
        $template = new Template($dto->get('template'));

        return new PlainTextMessage($this->html_render->render($template, ['doc_url' => 'https://flexi.cubadevops.com'])
        );
    }
}
