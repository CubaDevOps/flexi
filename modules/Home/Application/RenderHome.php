<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Home\Application;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\Interfaces\DTOInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\HandlerInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\MessageInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\TemplateEngineInterface;
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
        return new PlainTextMessage(
            $this->html_render->render(
                $dto->get('template'),
                ['doc_url' => 'https://flexi.cubadevops.com']
            )
        );
    }
}
