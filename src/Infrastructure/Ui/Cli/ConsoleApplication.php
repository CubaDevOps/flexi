<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Ui\Cli;

use CubaDevOps\Flexi\Domain\Classes\CommandBus;
use CubaDevOps\Flexi\Domain\Classes\EventBus;
use CubaDevOps\Flexi\Domain\Classes\QueryBus;
use CubaDevOps\Flexi\Domain\Factories\ContainerFactory;
use CubaDevOps\Flexi\Infrastructure\Classes\Configuration;
use CubaDevOps\Flexi\Infrastructure\Factories\ConfigurationFactory;
use ErrorException;
use Exception;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use RuntimeException;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\ErrorHandler\ErrorHandler;

class ConsoleApplication
{
    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws ErrorException
     * @throws JsonException
     * @throws Exception
     */
    public static function run($argv): void
    {
        $config = ConfigurationFactory::getInstance();
        if ('true' === $config->get('DEBUG_MODE')) {
            Debug::enable();
        }
        echo ErrorHandler::call(static function () use ($argv, $config) {
            return self::handle($config, $argv);
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    private static function handle(Configuration $config, array $argv): string
    {
        $container = ContainerFactory::getInstance('./src/Config/services.json');

        try {
            $input = CliInputParser::parse($argv);
        } catch (Exception $e) {
            if ('true' === $config->get('DEBUG_MODE')) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            return ConsoleOutputFormatter::format($e->getMessage(), 'error');
        }


        try {
            if ($input->getType() === CliType::COMMAND) {
                $result = (new CommandHandler($container->get(CommandBus::class)))->handle($input);
            } elseif ($input->getType() === CliType::QUERY) {
                $result = (new QueryHandler($container->get(QueryBus::class)))->handle($input);
            } else {
                $result = (new EventHandler($container->get(EventBus::class)))->handle($input);
            }

            return ConsoleOutputFormatter::format($result);
        } catch (Exception $e) {
            if ('true' === $config->get('DEBUG_MODE')) {
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            return ConsoleOutputFormatter::format(
                $input->getType() . ': ' . $input->getCommandName() . ' not found.',
                'error'
            );
        }
    }

    /**
     * @return void
     */
    public static function printUsage(): void
    {
        echo '- Usage: ' . ConsoleOutputFormatter::format(
                '--command(-c)|--query(-q)',
                'green',
                false
            ) . ' command_name ' . ConsoleOutputFormatter::format('arg1=blabla arg2=blabla', 'green');
        echo '- Try ' . ConsoleOutputFormatter::format(
                'command:list',
                'green',
                false
            ) . ' or ' . ConsoleOutputFormatter::format(
                'query:list',
                'green',
                false
            ) . ' for a list of options' . PHP_EOL;
        echo '- Type command with ' . ConsoleOutputFormatter::format(
                '--help(-h)',
                'green',
                false
            ) . ' option for usage' . PHP_EOL;
    }
}
