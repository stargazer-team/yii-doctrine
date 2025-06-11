<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use const PHP_EOL;

final class CustomerTypeConfigurator
{
    /**
     * @throws Exception
     */
    public function add(array $customTypes): void
    {
        foreach ($customTypes as $name => $className) {
            if (!Type::hasType($name)) {
                Type::addType($name, $className);
            }
        }
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    public function registerDoctrineTypeMapping(Connection $connection, array $mappingTypes): void
    {
        $platform = $this->getDatabasePlatform($connection);

        foreach ($mappingTypes as $dbType => $doctrineType) {
            $platform->registerDoctrineTypeMapping($dbType, $doctrineType);
        }
    }

    /**
     * @throws ConnectionException
     * @throws Exception
     */
    private function getDatabasePlatform(Connection $connection): AbstractPlatform
    {
        try {
            return $connection->getDatabasePlatform();
        } catch (DriverException $driverException) {
            throw new ConnectionException(
                'An exception occurred while establishing a connection to figure out your platform version.' . PHP_EOL .
                "You can circumvent this by setting a 'server_version' configuration value" . PHP_EOL . PHP_EOL .
                'For further information have a look at:' . PHP_EOL,
                0,
                $driverException,
            );
        }
    }
}
