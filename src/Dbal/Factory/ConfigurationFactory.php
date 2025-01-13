<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Dbal\Factory;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Schema\SchemaManagerFactory;
use RuntimeException;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Doctrine\Dbal\Enum\ConfigOptions;

use function array_map;
use function sprintf;

final class ConfigurationFactory
{
    public function __construct(
        private readonly Injector $injector,
    ) {
    }

    /**
     * @psalm-param array{
     *     auto_commit: bool,
     *     events: array<array-key, mixed>,
     *     middlewares: array<array-key, class-string<\Doctrine\DBAL\Driver\Middleware>>|empty,
     *     params: array<string, mixed>,
     *     schema_assets_filter: callable,
     *     mapping_types: array<string, string>,
     *     disable_type_comments: bool,
     *     schema_manager_factory: class-string<SchemaManagerFactory>
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

        $this->configureDisableTypeComments($configuration, $dbalConfig[ConfigOptions::DISABLE_TYPE_COMMENTS] ?? null);

        $this->configureSchemaManagerFactory(
            $configuration,
            $dbalConfig[ConfigOptions::SCHEMA_MANAGER_FACTORY] ?? null,
        );

        return $configuration;
    }


    private function configureAutoCommit(Configuration $configuration, ?bool $autoCommit): void
    {
        if (null === $autoCommit) {
            return;
        }

        $configuration->setAutoCommit($autoCommit);
    }

    private function configureDisableTypeComments(Configuration $configuration, ?bool $disableTypeComments): void
    {
        if (null === $disableTypeComments) {
            return;
        }

        if (!$disableTypeComments) {
            return;
        }

        $configuration->setDisableTypeComments($disableTypeComments);
    }

    private function configureSchemaAssetsFilter(Configuration $configuration, ?callable $schemaAssetsFilter): void
    {
        if (null === $schemaAssetsFilter) {
            return;
        }

        $configuration->setSchemaAssetsFilter($schemaAssetsFilter);
    }

    private function configureSchemaManagerFactory(
        Configuration $configuration,
        ?string $configureSchemaManagerFactoryClass
    ): void {
        if (null === $configureSchemaManagerFactoryClass) {
            return;
        }

        $schemaManagerFactory = $this->injector->make($configureSchemaManagerFactoryClass);

        if (!$schemaManagerFactory instanceof SchemaManagerFactory) {
            throw new RuntimeException(
                sprintf('Class %s not instance %s', $configureSchemaManagerFactoryClass, SchemaManagerFactory::class)
            );
        }

        $configuration->setSchemaManagerFactory($schemaManagerFactory);
    }
}
