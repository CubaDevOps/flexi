<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Modules\ErrorHandling\Infrastructure\Controllers;

use CubaDevOps\Flexi\Contracts\Classes\HttpHandler;
use CubaDevOps\Flexi\Contracts\Classes\Traits\FileHandlerTrait;
use CubaDevOps\Flexi\Contracts\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Contracts\Interfaces\TemplateEngineInterface;
use CubaDevOps\Flexi\Contracts\ValueObjects\LogLevel;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class NotFoundController extends HttpHandler
{
    use FileHandlerTrait;

    private TemplateEngineInterface $html_render;
    private SessionStorageInterface $session;
    private LoggerInterface $logger;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        TemplateEngineInterface $html_render,
        SessionStorageInterface $session,
        LoggerInterface $logger
    ) {
        parent::__construct($response_factory);
        $this->html_render = $html_render;
        $this->session = $session;
        $this->logger = $logger;
    }

    protected function process(ServerRequestInterface $request): ResponseInterface
    {
        $previous_url = $this->session->has('previous_route')
            ? $this->session->get('previous_route')
            : '';

        $body = $this->html_render->render(
            $this->normalize('./modules/ErrorHandling/Infrastructure/Ui/Templates/404.html'),
            ['request' => $previous_url]
        );

        $this->logger->log(LogLevel::NOTICE, 'Page not found', [
            $previous_url,
            __CLASS__,
        ]);
        $this->session->remove('previous_route');
        $response = $this->createResponse(404);
        $response->getBody()->write($body);

        return $response;
    }
}
