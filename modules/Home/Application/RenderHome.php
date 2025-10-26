<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\Home\Application;

use CubaDevOps\Flexi\Contracts\Classes\PlainTextMessage;
use CubaDevOps\Flexi\Contracts\DTOContract;
use CubaDevOps\Flexi\Contracts\HandlerContract;
use CubaDevOps\Flexi\Contracts\MessageContract;
use CubaDevOps\Flexi\Contracts\TemplateEngineContract;
use CubaDevOps\Flexi\Modules\Home\Domain\HomePageDTO;

class RenderHome implements HandlerContract
{
    private TemplateEngineContract $html_render;

    public function __construct(TemplateEngineContract $html_render)
    {
        $this->html_render = $html_render;
    }

    /**
     * @param HomePageDTO $dto
     */
    public function handle(DTOContract $dto): MessageContract
    {
        return new PlainTextMessage(
            $this->html_render->render(
                $dto->get('template'),
                ['doc_url' => 'https://flexi.cubadevops.com']
            )
        );
    }
}
