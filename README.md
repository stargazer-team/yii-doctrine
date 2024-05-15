<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii3 Doctrine Extension</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/stargazer-team/yii-doctrine/v/stable.png)](https://packagist.org/packages/stargazer-team/yii-doctrine)
[![Total Downloads](https://poser.pugx.org/stargazer-team/yii-doctrine/downloads.png)](https://packagist.org/packages/stargazer-team/yii-doctrine)
[![Build status](https://github.com/stargazer-team/yii-doctrine/workflows/build/badge.svg)](https://github.com/stargazer-team/yii-doctrine/actions)
[![Code Coverage](https://scrutinizer-ci.com/g/stargazer-team/yii-doctrine/badges/coverage.png)](https://scrutinizer-ci.com/g/stargazer-team/yii-doctrine/)
[![Scrutinizer Quality Score](https://scrutinizer-ci.com/g/stargazer-team/yii-doctrine/badges/quality-score.png)](https://scrutinizer-ci.com/g/stargazer-team/yii-doctrine/)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fyiisoft%2Fyii-console%2Fmaster)](https://dashboard.stryker-mutator.io/reports/github.com/stargazer-team/yii-doctrine/master)
[![static analysis](https://github.com/stargazer-team/yii-doctrine/workflows/static%20analysis/badge.svg)](https://github.com/stargazer-team/yii-doctrine/actions?query=workflow%3A%22static+analysis%22)
[![type-coverage](https://shepherd.dev/github/stargazer-team/yii-doctrine/coverage.svg)](https://shepherd.dev/github/stargazer-team/yii-doctrine)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/):

```bash
composer require stargazer-team/yii-doctrine
```

Basic Usage
-----------

Configuration params doctrine: dbal, orm, migrations, fixture example config path [example.php](config/example.php)

### DBAL

Create database:
```bash
php yii doctrine:database:create
```

Drop database:
```bash
php yii doctrine:database:drop --if-exists --force
```

Dynamic create connection:
```php
final class ConnectionService
{
    public function __construct(
        private readonly \Yiisoft\Yii\Doctrine\Dbal\Factory\DynamicConnectionFactory $dynamicConnectionFactory,
        private readonly \Yiisoft\Yii\Doctrine\DoctrineManager $doctrineManager,
    ) {
    }
    
    public function create(): void
    {
        $connectionModel = $this->dynamicConnectionFactory->createConnection(
            [
                'params' => [
                    'driver' => 'pdo_pgsql',
                    'dbname' => 'dbname',
                    'host' => 'localhost',
                    'password' => 'secret',
                    'user' => 'postgres',
                ]
            ],
            'postgres'
        );
    }
    
    public function close(): void
    {
        $this->doctrineManager->closeConnection('postgres');
    }
}
```

Command:
 - doctrine:dbal:run-sql
 - doctrine:database:create
 - doctrine:database:drop

### ORM

If need default entity manager add on di.php

```php
EntityManagerInterface::class => fn(
    DoctrineManager $doctrineManager
): EntityManagerInterface => $doctrineManager->getManager(
        $params['yiisoft/yii-doctrine']['orm']['default_entity_manager'] ?? DoctrineManager::DEFAULT_ENTITY_MANAGER
   ),
```

Use default entity manager:
```php
final class TestController
{
    public function __construct(
        private readonly \Doctrine\ORM\EntityManagerInterface $entityManager,
    ) {
    }
}
```

If two or more entity manager use Yiisoft\Yii\Doctrine\Doctrine\DoctrineManager, find by name entity manager
```php
final class Test2Controller
{
    public function __construct(
        private readonly Yiisoft\Yii\Doctrine\DoctrineManager $doctrineManager,
    ) {
    }
}
```

Dynamic create entity manager:
```php
final class EntityManagerService
{
    public function __construct(
        private readonly \Yiisoft\Yii\Doctrine\Orm\Factory\DynamicEntityManagerFactory $dynamicEntityManagerFactory,
        private readonly \Yiisoft\Yii\Doctrine\DoctrineManager $doctrineManager
    ) {
    }
    
    public function create(): void
    {
        $this->dynamicEntityManagerFactory->create(
            [
                'connection' => 'mysql',
                'mappings' => [
                    'Mysql' => [
                        'dir' => '@common/Mysql',
                        'driver' => DriverMappingEnum::ATTRIBUTE_MAPPING,
                        'namespace' => 'Common\Mysql',
                    ],
                ],
            ],
            [
                'namespace' => 'Proxies',
                'path' => '@runtime/cache/doctrine/proxy',
                'auto_generate' => true
            ],
            'mysql'
        );

        $entityManager = $this->doctrineManager->getManager('mysql');
    }
    
    public function reset(): void
    {
        $this->doctrineManager->resetManager('mysql');
    }
    
    public function close(): void
    {
        $this->doctrineManager->closeManager('mysql');
    }
}
```

Command:
 - doctrine:orm:info
 - doctrine:orm:generate-proxies
 - doctrine:orm:mapping-describe
 - doctrine:orm:run-dql
 - doctrine:orm:validate-schema
 - doctrine:orm:schema-tool:create
 - doctrine:orm:schema-tool:drop
 - doctrine:orm:schema-tool:update

#### Cache

Extension use psr6 cache implementation symfony cache.

Options add config on params.php

```php
return [
    'yiisoft/yii-doctrine' => [
        // Used symfony cache
        'cache' => [
            'driver' => CacheAdapterEnum::ARRAY_ADAPTER,
            // only redis or memcached
            'server' => [
                'host' => 'localhost',
                'port' => 6379
            ],
            'namespace' => 'doctrine_',
            // only file cache driver
            'path' => '@runtime/cache/doctrine',
        ],
];
```
Add on di.php configuration psr-6 cache
```php
CacheItemPoolInterface::class => fn(CacheFactory $cacheFactory): CacheItemPoolInterface => $cacheFactory->create(
    $params['yiisoft/yii-doctrine']['cache'] ?? []
),
```
or add di.php customer implementation

```php
CacheItemPoolInterface::class => new \Symfony\Component\Cache\Adapter\ArrayAdapter(),
```

Command:
 - doctrine:orm:clear-cache:metadata
 - doctrine:orm:clear-cache:query
 - doctrine:orm:clear-cache:result

### Migrations
Create migration:
```bash
php yii doctrine:migrations:diff
```
Multi configuration
```bash
php yii doctrine:migrations:diff --configuration=mysql
```

Migrate
```bash
php yii doctrine:migrations:migrate
```
Command:
 - doctrine:migrations:current
 - doctrine:migrations:diff
 - doctrine:migrations:dump-schema
 - doctrine:migrations:execute
 - doctrine:migrations:generate
 - doctrine:migrations:latest
 - doctrine:migrations:list
 - doctrine:migrations:migrate
 - doctrine:migrations:rollup
 - doctrine:migrations:status
 - doctrine:migrations:sync-metadata-storage
 - doctrine:migrations:up-to-date
 - doctrine:migrations:version

### Fixture
If need fixture, install package
```bash
composer require stargazer-team/yii-doctrine-fixture --dev
```
