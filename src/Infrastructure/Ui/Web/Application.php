<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Web;

use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Factories\RouterFactory;
use CubaDevOps\Flexi\Infrastructure\Http\Router;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;

class Application
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws \ErrorException
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws InvalidArgumentException
     */
    public function run(): void
    {
        $config = $this->container->get(Configuration::class);
        if ('true' === $config->get('DEBUG_MODE')) {
            Debug::enable();
        }
        echo ErrorHandler::call(function () {
            return $this->handle();
        });
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     * @throws InvalidArgumentException
     */
    private function handle(): StreamInterface
    {
        /** @var Router $router */
        $router = $this->container->get(RouterFactory::class)->getInstance('./src/Config/routes.json');
        $response = $router->dispatch(ServerRequest::fromGlobals());

        return $this->sendResponse($response);
    }

    private function sendResponse(ResponseInterface $response): StreamInterface
    {
        header(
            "HTTP/1.1 {$response->getStatusCode()} {$response->getReasonPhrase()}"
        );
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }

        return $response->getBody();
    }
}
