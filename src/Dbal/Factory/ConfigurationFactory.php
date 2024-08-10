<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Dbal\Factory;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Types\Type;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Doctrine\Dbal\Enum\ConfigOptions;

final class ConfigurationFactory
{
    public function __construct(
        private readonly Injector $injector,
    ) {
    }

    /**
     * @psalm-param array{
     *     auto_commit: bool,
     *     custom_types: array<string, class-string<Type>>,
     *     events: array<array-key, mixed>,
     *     middlewares: array<array-key, class-string<\Doctrine\DBAL\Driver\Middleware>>|empty,
     *     params: array<string, mixed>,
     *     schema_assets_filter: callable
     * } $dbalConfig
     */
    public function create(array $dbalConfig): Configuration
    {
        $configuration = new Configuration();

        $middlewares = array_map(
            function (string $classMiddleware): Middleware {
                return $this->injector->make($classMiddleware);
            },
            $dbalConfig[ConfigOptions::MIDDLEWARES] ?? []
        );

        $configuration->setMiddlewares($middlewares);

        $this->configureAutoCommit($configuration, $dbalConfig[ConfigOptions::AUTO_COMMIT] ?? null);

        $this->configureSchemaAssetsFilter($configuration, $dbalConfig[ConfigOptions::SCHEMA_ASSETS_FILTER] ?? null);

        return $configuration;
    }


    private function configureAutoCommit(Configuration $configuration, ?bool $autoCommit): void
    {
        if (null === $autoCommit) {
            return;
        }

        $configuration->setAutoCommit($autoCommit);
    }

    private function configureSchemaAssetsFilter(Configuration $configuration, ?callable $schemaAssetsFilter): void
    {
        if (null === $schemaAssetsFilter) {
            return;
        }

        $configuration->setSchemaAssetsFilter($schemaAssetsFilter);
    }
}
