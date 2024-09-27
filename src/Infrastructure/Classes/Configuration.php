<?php

declare(strict_types=1);

namespace CubaDevOps\Flexi\Infrastructure\Classes;

use CubaDevOps\Flexi\Domain\Classes\Collection;
use CubaDevOps\Flexi\Domain\ValueObjects\CollectionType;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;

class Configuration implements ContainerInterface
{
    private Collection $config;

    public function __construct()
    {
        $this->config = new Collection(new CollectionType('string'));
        $this->init();
    }

    private function init(): void
    {
        $root_dir = dirname(__DIR__, 3);
        $dotenv = Dotenv::createUnsafeImmutable($root_dir);
        $dotenv->safeLoad();
        foreach ($_ENV as $key => $value) {
            $this->config->add($value, $key);
        }
        $this->config->add($root_dir, 'ROOT_DIR');
        $this->config->add($root_dir.'/src', 'APP_DIR');
        $this->config->add($root_dir.'/modules', 'MODULES_DIR');
        $debug = getenv('debug');
        $this->config->add($debug, 'DEBUG_MODE');
    }

    public function get(string $id)
    {
        return $this->config->get($id);
    }

    public function has(string $id): bool
    {
        return $this->config->has($id);
    }

    public function isDispatchModeEnabled(): bool
    {
        return $this->config->get('dispatch_mode') ?? false;
    }
}
