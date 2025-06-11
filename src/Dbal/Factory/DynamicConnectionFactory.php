<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Dbal\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\SchemaManagerFactory;
use RuntimeException;
use Yiisoft\Yii\Doctrine\DoctrineManager;

use function sprintf;

final class DynamicConnectionFactory
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory,
        private readonly DoctrineManager $doctrineManager,
    ) {
    }

    /**
     * @psalm-param array{
     *     auto_commit?: bool,
     *     middlewares?: array<array-key, class-string<\Doctrine\DBAL\Driver\Middleware>>,
     *     params: array<string, mixed>,
     *     schema_assets_filter?: callable,
     *     mapping_types?: array<string, string>,
     *     disable_type_comments?: bool,
     *     schema_manager_factory?: class-string<SchemaManagerFactory>
     * } $dbalConfig
     * @throws Exception
     */
    public function createConnection(array $dbalConfig, string $connectionName): Connection
    {
        if ($this->doctrineManager->hasConnection($connectionName)) {
            throw new RuntimeException(sprintf('Connection "%s" already exist', $connectionName));
        }

        $connection = $this->connectionFactory->create($dbalConfig);

        $this->doctrineManager->addConnection($connectionName, $connection);

        return $connection;
    }
}
