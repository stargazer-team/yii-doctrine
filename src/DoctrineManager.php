<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\Persistence\Proxy;
use ReflectionClass;
use ReflectionException;
use RuntimeException;

use function array_keys;
use function assert;
use function sprintf;

final class DoctrineManager implements ManagerRegistry
{
    public const DEFAULT_CONNECTION = 'default';
    public const DEFAULT_ENTITY_MANAGER = 'default';

    /** @psalm-var ReflectionClass<object>|class-string */
    private string $proxyInterfaceName = Proxy::class;

    /**
     * @psalm-param array<string, object<Connection>> $connections
     * @psalm-param array<string, object<EntityManagerInterface>> $managers
     */
    public function __construct(
        private array $connections,
        private array $managers,
        private readonly string $defaultConnection,
        private readonly string $defaultManager,
    ) {
    }

    public function addConnection(string $name, Connection $connection): void
    {
        if (isset($this->connections[$name])) {
            throw new RuntimeException(sprintf('Connection by name "%s" already exists', $name));
        }

        $this->connections[$name] = $connection;
    }

    public function addManager(string $name, EntityManagerInterface $entityManager): void
    {
        if (isset($this->managers[$name])) {
            throw new RuntimeException(sprintf('Entity manager by name "%s" already exists', $name));
        }

        $this->managers[$name] = $entityManager;
    }

    public function closeConnection(string $name): void
    {
        $connection = $this->connections[$name] ?? null;

        if (null === $connection) {
            throw new RuntimeException(sprintf('Connection by name "%s" already is not exists', $name));
        }

        $connection->close();

        unset($this->connections[$name]);
    }

    public function closeManager(string $name): void
    {
        $manager = $this->managers[$name] ?? null;

        if (null === $manager) {
            throw new RuntimeException(sprintf('Entity manager by name "%s" already is not exists', $name));
        }

        $manager->close();

        unset($this->managers[$name]);
    }

    public function flushAllManager(): void
    {
        foreach ($this->managers as $manager) {
            $manager->flush();
        }
    }

    public function flushManager(?string $name = null): void
    {
        if (null === $name) {
            $name = $this->getDefaultManagerName();
        }

        $entityManager = $this->managers[$name] ?? null;

        if (null === $entityManager) {
            throw new RuntimeException(sprintf('Not found entity manager "%s"', $name));
        }

        $entityManager->flush();
    }

    public function getConnection(?string $name = null): Connection
    {
        if (null === $name) {
            $name = $this->getDefaultConnectionName();
        }

        $connection = $this->connections[$name] ?? null;

        if (null === $connection) {
            throw new RuntimeException(sprintf('Not found connection by name "%s"', $name));
        }

        return $connection;
    }

    /**
     * @psalm-return list<array-key>
     */
    public function getConnectionNames(): array
    {
        return array_keys($this->connections);
    }

    public function getConnections(): array
    {
        return $this->connections;
    }

    public function getDefaultConnectionName(): string
    {
        return $this->defaultConnection;
    }

    public function getDefaultManagerName(): string
    {
        return $this->defaultManager;
    }

    public function getManager(?string $name = null): EntityManagerInterface
    {
        if (null === $name) {
            $name = $this->getDefaultManagerName();
        }

        $entityManager = $this->managers[$name] ?? null;

        if (null === $entityManager) {
            throw new RuntimeException(sprintf('Not found entity manager by name "%s"', $name));
        }

        return $entityManager;
    }

    /**
     * @throws ReflectionException
     */
    public function getManagerForClass(string $class): ?EntityManagerInterface
    {
        $proxyClass = new ReflectionClass($class);

        if ($proxyClass->isAnonymous()) {
            return null;
        }

        if ($proxyClass->implementsInterface($this->proxyInterfaceName)) {
            $parentClass = $proxyClass->getParentClass();

            if ($parentClass === false) {
                return null;
            }

            $class = $parentClass->getName();
        }

        foreach ($this->managers as $manager) {
            assert($manager instanceof ObjectManager);

            if (!$manager->getMetadataFactory()->isTransient($class)) {
                return $manager;
            }
        }

        return null;
    }

    /**
     * @psalm-return list<array-key>
     */
    public function getManagerNames(): array
    {
        return array_keys($this->managers);
    }

    /**
     * @psalm-return array<string, ObjectManager>
     */
    public function getManagers(): array
    {
        return $this->managers;
    }

    /**
     * @throws ReflectionException
     */
    public function getRepository(
        string $persistentObject,
        ?string $persistentManagerName = null
    ): EntityRepository|ObjectRepository
    {
        return $this
            ->selectManager($persistentObject, $persistentManagerName)
            ->getRepository($persistentObject);
    }

    public function hasConnection(string $name): bool
    {
        return isset($this->connections[$name]);
    }

    public function hasManager(string $name): bool
    {
        return isset($this->managers[$name]);
    }

    public function resetAllManager(): void
    {
        foreach ($this->managers as $manager) {
            $manager->clear();
        }
    }

    public function resetManager(?string $name = null): ObjectManager
    {
        if (null === $name) {
            $name = $this->getDefaultManagerName();
        }

        $entityManager = $this->managers[$name] ?? null;

        if (null === $entityManager) {
            throw new RuntimeException(sprintf('Not found entity manager "%s"', $name));
        }

        $entityManager->clear();

        return $entityManager;
    }

    /**
     * @psalm-param class-string $persistentObject
     * @throws ReflectionException
     */
    private function selectManager(string $persistentObject, ?string $persistentManagerName = null): ObjectManager
    {
        if ($persistentManagerName !== null) {
            return $this->getManager($persistentManagerName);
        }

        return $this->getManagerForClass($persistentObject) ?? $this->getManager();
    }
}
