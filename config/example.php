<?php

declare(strict_types=1);

use Doctrine\DBAL\Schema\Sequence;
use Doctrine\ORM\Events;
use Yiisoft\Yii\Doctrine\DoctrineManager;
use Yiisoft\Yii\Doctrine\Orm\Enum\DriverMappingEnum;

return [
    'yiisoft/yii-doctrine' => [
        'dbal' => [
            'default' => [
                // check params https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/configuration.html
                'params' => [
                    'driver' => 'pdo_pgsql',
                    'dbname' => 'dbname',
                    'host' => 'localhost',
                    'password' => 'secret',
                    'user' => 'postgres',
                ],
                'custom_types' => [
//                    UuidType::NAME => UuidType::class
                ],
                'auto_commit' => false,
//                'schema_assets_filter' => static function (string|Sequence $table): bool {
//                    if (is_string($table)) {
//                        return $table === 'geo_locations';
//                    }
//
//                    return true;
//                },
                'middlewares' => [
                    // logger middleware
                    Doctrine\DBAL\Logging\Middleware::class
                ]
            ],
            'mysql' => [
                'params' => [
                    'driver' => 'pdo_mysql',
                    'dbname' => 'dbname',
                    'host' => 'localhost',
                    'password' => 'secret',
                    'user' => 'root',
                ],
            ]
        ],
        'orm' => [
            'proxies' => [
                'namespace' => 'Proxies',
                'path' => '@runtime/cache/doctrine/proxy',
                'auto_generate' => true
            ],
            'default_entity_manager' => DoctrineManager::DEFAULT_ENTITY_MANAGER,
            'entity_managers' => [
                'default' => [
                    'connection' => 'default',
//                  'naming_strategy' => NamingStrategy::class,
//                  'class_metadata_factory_name' => 'MetadataFactory::class',
//                  'default_repository_class' => 'DefaultRepository::class',
//                  'schema_ignore_classes' => [],
//                  'entity_listener_resolver' => DefaultEntityListenerResolver::class,
//                  'typed_field_mapper' => DefaultTypedFieldMapper::class,
                    'mappings' => [
                        'User' => [
                            'dir' => '@src/User/Entity',
                            'driver' => DriverMappingEnum::ATTRIBUTE_MAPPING,
                            'namespace' => 'App\User\Entity',
                        ],
                    ],
                    'dql' => [
                        'custom_datetime_functions' => [
//                            'ADDTIME' => AddTime::class
                        ],
                        'custom_numeric_functions' => [
//                            'CEIL' => Ceil::class
                        ],
                        'custom_string_functions' => [
//                            'MD5' => Md5::class
                        ],
                    ],
                    'custom_hydration_modes' => [
                        // HydrationMode::class
                    ],
                    'events' => [
                        'listeners' => [
                            Events::preFlush => [
//                            EventOrmListener::class
                            ],
                        ],
                        'subscribers' => [
//                        EventOrmSubscriber::class,
                        ]
                    ],
                    'filters' => [
                        // Filter::class,
                    ],
                ],
                'mysql' => [
                    'connection' => 'mysql',
                    'mappings' => [
                        'User' => [
                            'dir' => '@src/Mysql/User/Entity',
                            'driver' => DriverMappingEnum::ATTRIBUTE_MAPPING,
                            'namespace' => 'App\Mysql\User\Entity',
                        ],
                    ],
                ]
            ],
        ],
    ],
    // configuration params https://www.doctrine-project.org/projects/doctrine-migrations/en/3.6/reference/configuration.html#configuration
    'yiisoft/yii-doctrine-migrations' => [
        'default' => [
            'table_storage' => [
                'table_name' => 'postgres_migration_versions',
                'version_column_name' => 'version',
                'version_column_length' => 1024,
                'executed_at_column_name' => 'executed_at',
                'execution_time_column_name' => 'execution_time',
            ],
            'migrations_paths' => [
                'App\Migrations\Postgres' => '@src/Migrations/Postgres',
            ],
            'all_or_nothing' => true,
            'check_database_platform' => true,
            // if using only dbal
//        'connection' => 'default',
            // if using only orm entity manager
            'em' => 'default',
        ],
        'mysql' => [
            'table_storage' => [
                'table_name' => 'mysql_migration_versions',
                'version_column_name' => 'version',
                'version_column_length' => 1024,
                'executed_at_column_name' => 'executed_at',
                'execution_time_column_name' => 'execution_time',
            ],
            'migrations_paths' => [
                'App\Migrations\Mysql' => '@src/Migrations/Mysql',
            ],
            'all_or_nothing' => true,
            'check_database_platform' => true,
            // if using only dbal
//        'connection' => 'mysql',
            // if using only orm entity manager
            'em' => 'mysql',
        ],
    ],
];
