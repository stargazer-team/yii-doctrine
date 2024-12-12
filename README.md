<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii3 Doctrine Extension</h1>
    <br>
</p>

[![Latest Stable Version](https://poser.pugx.org/stargazer-team/yii-doctrine/v)](https://packagist.org/packages/stargazer-team/yii-doctrine)
[![Total Downloads](https://poser.pugx.org/stargazer-team/yii-doctrine/downloads)](https://packagist.org/packages/stargazer-team/yii-doctrine)
[![Build status](https://github.com/stargazer-team/yii-doctrine/actions/workflows/php.yml/badge.svg)](https://github.com/stargazer-team/yii-doctrine/actions)
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
<?php

declare(strict_types=1);

use Yiisoft\Yii\Doctrine\Dbal\Enum\ConfigOptions;
use Yiisoft\Yii\Doctrine\Dbal\Factory\DynamicConnectionFactory;
use Yiisoft\Yii\Doctrine\DoctrineManager;

final class ConnectionService
{
    public function __construct(
        private readonly DynamicConnectionFactory $dynamicConnectionFactory,
        private readonly DoctrineManager $doctrineManager,
    ) {
    }
    
    public function create(): void
    {
        $connectionModel = $this->dynamicConnectionFactory->createConnection(
            [
                ConfigOptions::PARAMS => [
                    'driver' => 'pdo_pgsql',
                    'dbname' => 'dbname',
                    'host' => 'localhost',
                    'password' => 'secret',
                    'user' => 'postgres',
                ]
            ],
            'postgres',
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
<?php

declare(strict_types=1);

use Yiisoft\Yii\Doctrine\DoctrineManager
use Yiisoft\Yii\Doctrine\Orm\Enum\ConfigOptions;

EntityManagerInterface::class => fn(
    DoctrineManager $doctrineManager
): EntityManagerInterface => $doctrineManager->getManager(
        $params['yiisoft/yii-doctrine'][ConfigOptions::ORM][ConfigOptions::DEFAULT_ENTITY_MANAGER] ?? DoctrineManager::DEFAULT_ENTITY_MANAGER,
   ),
```

Use default entity manager:
```php
<?php

declare(strict_types=1);

use Doctrine\ORM\EntityManagerInterface;

final class TestController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }
}
```

If two or more entity manager use Yiisoft\Yii\Doctrine\Doctrine\DoctrineManager, find by name entity manager
```php
<?php

declare(strict_types=1);

use Yiisoft\Yii\Doctrine\DoctrineManager;

final class Test2Controller
{
    public function __construct(
        private readonly DoctrineManager $doctrineManager,
    ) {
    }
}
```

Dynamic create entity manager:
```php
<?php

declare(strict_types=1);

use Yiisoft\Yii\Doctrine\DoctrineManager;
use Yiisoft\Yii\Doctrine\Orm\Enum\ConfigOptions;
use Yiisoft\Yii\Doctrine\Orm\Factory\DynamicEntityManagerFactory;

final class EntityManagerService
{
    public function __construct(
        private readonly DynamicEntityManagerFactory $dynamicEntityManagerFactory,
        private readonly DoctrineManager $doctrineManager,
    ) {
    }
    
    public function create(): void
    {
        $this->dynamicEntityManagerFactory->create(
            [
                ConfigOptions::CONNECTION => 'mysql',
                ConfigOptions::MAPPINGS => [
                    'Mysql' => [
                        ConfigOptions::MAPPING_DIR => '@common/Mysql',
                        ConfigOptions::MAPPING_DRIVER => DriverMappingEnum::ATTRIBUTE_MAPPING,
                        ConfigOptions::MAPPING_NAMESPACE => 'Common\Mysql',
                    ],
                ],
            ],
            [
                ConfigOptions::PROXY_NAMESPACE => 'Proxies',
                ConfigOptions::PROXY_PATH => '@runtime/cache/doctrine/proxy',
                ConfigOptions::PROXY_AUTO_GENERATE => true
            ],
            'mysql',
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
Default cache Symfony\Component\Cache\Adapter\NullAdapter.

Options add config on params.php

```php
<?php

declare(strict_types=1);

use Yiisoft\Yii\Doctrine\Cache\CacheCollector;
use Yiisoft\Yii\Doctrine\Cache\Enum\ConfigOptions;

return [
    'yiisoft/yii-doctrine' => [
        // Used symfony cache
        ConfigOptions::CACHE => [
            ConfigOptions::DRIVER => CacheAdapterEnum::ARRAY_ADAPTER,
            // only redis or memcached
            ConfigOptions::SERVER => [
                ConfigOptions::HOST => 'localhost',
                ConfigOptions::PORT => 6379
            ],
            ConfigOptions::NAMESPACE => 'doctrine_',
            // only file cache driver
            ConfigOptions::PATH => '@runtime/cache/doctrine',
        ],
];
```
Add on di.php configuration psr-6 cache
```php
<?php

declare(strict_types=1);

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Yiisoft\Yii\Doctrine\Cache\CacheCollector

return [
    CacheCollector::DOCTRINE_HYDRATION_CACHE => fn(CacheFactory $cacheFactory): CacheItemPoolInterface => $cacheFactory->create(
        $params['yiisoft/yii-doctrine']['cache'] ?? []
    ),
    // or add di.php customer implementation
    CacheCollector::DOCTRINE_HYDRATION_CACHE => ArrayAdapter(),        
    CacheCollector::DOCTRINE_METADATA_CACHE => ArrayAdapter(),        
    CacheCollector::DOCTRINE_QUERY_CACHE => ArrayAdapter(),        
    CacheCollector::DOCTRINE_RESULT_CACHE => ArrayAdapter(),        
    
];

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
