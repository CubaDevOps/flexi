<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Web;

use CubaDevOps\Flexi\Domain\Classes\Router;
use CubaDevOps\Flexi\Domain\Factories\ContainerFactory;
use CubaDevOps\Flexi\Infrastructure\Factories\ConfigurationFactory;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;

class Application
{
    /**
     * @throws ContainerExceptionInterface
     * @throws \ErrorException
     * @throws \JsonException
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     */
    public static function run(): void
    {
        $config = ConfigurationFactory::getInstance();
        if ('true' === $config->get('DEBUG_MODE')) {
            Debug::enable();
        }
        echo ErrorHandler::call(static function () {
            return self::handle();
        });
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws ContainerExceptionInterface
     * @throws \JsonException
     */
    private static function handle(): StreamInterface
    {
        $container = ContainerFactory::getInstance('./src/Config/services.json');

        /** @var Router $router */
        $router = $container->get(Router::class);
        $response = $router->dispatch(ServerRequest::fromGlobals());

        return self::sendResponse($response);
    }

    private static function sendResponse(ResponseInterface $response): StreamInterface
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
