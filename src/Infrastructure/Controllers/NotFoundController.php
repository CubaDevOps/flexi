<?php

namespace CubaDevOps\Flexi\Infrastructure\Controllers;

use CubaDevOps\Flexi\Domain\Classes\Template;
use CubaDevOps\Flexi\Domain\Interfaces\SessionStorageInterface;
use CubaDevOps\Flexi\Domain\Interfaces\TemplateEngineInterface;
use CubaDevOps\Flexi\Domain\ValueObjects\LogLevel;
use CubaDevOps\Flexi\Infrastructure\Classes\HttpHandler;
use CubaDevOps\Flexi\Infrastructure\Factories\ConfigurationFactory;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class NotFoundController extends HttpHandler
{
    private TemplateEngineInterface $html_render;
    private SessionStorageInterface $session;
    private LoggerInterface $logger;

    public function __construct(
        TemplateEngineInterface $html_render,
        SessionStorageInterface $session,
        LoggerInterface $logger
    ) {
        $this->html_render = $html_render;
        $this->session = $session;
        $this->logger = $logger;
        parent::__construct();
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $app_dir = ConfigurationFactory::getInstance()->get('APP_DIR');
        $template = new Template(
            $app_dir . '/Infrastructure/Ui/Templates/404.html'
        );

        $previous_url = $this->session->has('previous_route')
            ? $this->session->get('previous_route')
            : '';
        $body = $this->html_render->render($template, [
            'request' => $previous_url,
        ]);
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
