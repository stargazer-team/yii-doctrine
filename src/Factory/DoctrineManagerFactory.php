<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use RuntimeException;
use Yiisoft\Yii\Doctrine\Dbal\CustomerTypeConfigurator;
use Yiisoft\Yii\Doctrine\Dbal\Enum\ConfigOptions as DbalConfigOption;
use Yiisoft\Yii\Doctrine\Dbal\Factory\ConnectionFactory;
use Yiisoft\Yii\Doctrine\DoctrineManager;
use Yiisoft\Yii\Doctrine\Orm\Enum\ConfigOptions as OrmConfigOption;
use Yiisoft\Yii\Doctrine\Orm\Factory\EntityManagerFactory;

use function sprintf;

final class DoctrineManagerFactory
{
    public function __construct(
        private readonly ConnectionFactory $connectionFactory,
        private readonly CustomerTypeConfigurator $customerTypeConfigurator,
        private readonly EntityManagerFactory $entityManagerFactory,
    ) {
    }

    /**
     * @throws Exception
     */
    public function create(array $doctrineConfig): DoctrineManager
    {
        // configure customer types
        $this->customerTypeConfigurator->add(
            $doctrineConfig[DbalConfigOption::DBAL][DbalConfigOption::CUSTOM_TYPES] ?? [],
        );

        // init connections
        $connections = [];

        if (!empty($doctrineConfig[DbalConfigOption::DBAL][DbalConfigOption::CONNECTIONS])) {
            foreach ($doctrineConfig[DbalConfigOption::DBAL][DbalConfigOption::CONNECTIONS] as $name => $dbalConfig) {
                $connections[$name] = $this->connectionFactory->create($dbalConfig);
            }
        }

        // init entity managers
        $entityManagers = [];

        if (!empty($doctrineConfig[OrmConfigOption::ORM][OrmConfigOption::ENTITY_MANAGERS])) {
            foreach ($doctrineConfig[OrmConfigOption::ORM][OrmConfigOption::ENTITY_MANAGERS] as $name => $entityManagerConfig) {
                $connectionName = $entityManagerConfig[OrmConfigOption::CONNECTION] ?? null;

                if (null === $connectionName) {
                    throw new RuntimeException(
                        sprintf('Not found param "connection" on entity manager "%s"', $name),
                    );
                }

                /** @var Connection|null $connection */
                $connection = $connections[$connectionName] ?? null;

                if (null === $connection) {
                    throw new RuntimeException(
                        sprintf('Not found connection "%s"', $connectionName),
                    );
                }

                $entityManagers[$name] = $this->entityManagerFactory->create(
                    $connection,
                    $entityManagerConfig,
                    $doctrineConfig[OrmConfigOption::ORM][OrmConfigOption::PROXIES] ?? [],
                );
            }
        }

        return new DoctrineManager(
            $connections,
            $entityManagers,
            DoctrineManager::DEFAULT_CONNECTION,
            DoctrineManager::DEFAULT_CONNECTION,
        );
    }
}
