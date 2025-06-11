<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Dbal\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaManagerFactory;
use InvalidArgumentException;
use Yiisoft\Yii\Doctrine\Dbal\CustomerTypeConfigurator;
use Yiisoft\Yii\Doctrine\Dbal\Enum\ConfigOptions;

final class ConnectionFactory
{
    public function __construct(
        private readonly ConfigurationFactory $configurationFactory,
        private readonly CustomerTypeConfigurator $customerTypeConfigurator,
    ) {
    }

    /**
     * @psalm-param array{
     *     auto_commit?: bool,
     *     middlewares?: array<array-key, class-string<Middleware>>,
     *     params: array<string, mixed>,
     *     schema_assets_filter?: callable,
     *     mapping_types?: array<string, string>,
     *     disable_type_comments?: bool,
     *     schema_manager_factory?: class-string<SchemaManagerFactory>
     * } $dbalConfig
     *
     * @throws Exception
     */
    public function create(array $dbalConfig): Connection
    {
        if (!isset($dbalConfig[ConfigOptions::PARAMS])) {
            throw new InvalidArgumentException('Not found "params" connection');
        }

        $configuration = $this->configurationFactory->create($dbalConfig);

        $connection = DriverManager::getConnection($dbalConfig[ConfigOptions::PARAMS], $configuration);

        $this->customerTypeConfigurator->registerDoctrineTypeMapping(
            $connection,
            $dbalConfig[ConfigOptions::MAPPING_TYPES] ?? [],
        );

        return $connection;
    }
}
