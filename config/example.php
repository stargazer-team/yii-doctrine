<?php

declare(strict_types=1);

use Doctrine\ORM\Events;
use Yiisoft\Yii\Doctrine\Dbal\Enum\ConfigOptions as DbalConfigOption;
use Yiisoft\Yii\Doctrine\DoctrineManager;
use Yiisoft\Yii\Doctrine\Migrations\Enum\ConfigOptions as MigrationConfigOptions;
use Yiisoft\Yii\Doctrine\Orm\Enum\ConfigOptions as OrmConfigOptions;
use Yiisoft\Yii\Doctrine\Orm\Enum\DriverMappingEnum;

return [
    'yiisoft/yii-doctrine' => [
        DbalConfigOption::DBAL => [
            'default' => [
                // check params https://www.doctrine-project.org/projects/doctrine-dbal/en/current/reference/configuration.html
                DbalConfigOption::PARAMS => [
                    'driver' => 'pdo_pgsql',
                    'dbname' => 'dbname',
                    'host' => 'localhost',
                    'password' => 'secret',
                    'user' => 'postgres',
                ],
                DbalConfigOption::CUSTOM_TYPES => [
//                    UuidType::NAME => UuidType::class
                ],
                DbalConfigOption::AUTO_COMMIT => false,
//                DbalConfigOption::SCHEMA_ASSETS_FILTER => static function (string|Sequence $table): bool {
//                    if (is_string($table)) {
//                        return $table === 'geo_locations';
//                    }
//
//                    return true;
//                },
                DbalConfigOption::MIDDLEWARES => [
                    // logger middleware
                    Doctrine\DBAL\Logging\Middleware::class
                ]
            ],
            'mysql' => [
                DbalConfigOption::PARAMS => [
                    'driver' => 'pdo_mysql',
                    'dbname' => 'dbname',
                    'host' => 'localhost',
                    'password' => 'secret',
                    'user' => 'root',
                ],
            ]
        ],
        OrmConfigOptions::ORM => [
            OrmConfigOptions::PROXIES => [
                OrmConfigOptions::PROXY_NAMESPACE => 'Proxies',
                OrmConfigOptions::PROXY_PATH => '@runtime/cache/doctrine/proxy',
                OrmConfigOptions::PROXY_AUTO_GENERATE => true
            ],
            OrmConfigOptions::DEFAULT_ENTITY_MANAGER => DoctrineManager::DEFAULT_ENTITY_MANAGER,
            OrmConfigOptions::ENTITY_MANAGERS => [
                'default' => [
                    OrmConfigOptions::CONNECTION => 'default',
//                    OrmConfigOptions::QUOTE_STRATEGY => DefaultQuoteStrategy::class,
//                  OrmConfigOptions::NAMING_STRATEGY => NamingStrategy::class,
//                  OrmConfigOptions::CLASS_METADATA_FACTORY_NAME => 'MetadataFactory::class',
//                  OrmConfigOptions::DEFAULT_REPOSITORY_CLASS => 'DefaultRepository::class',
//                  OrmConfigOptions::SCHEMA_IGNORE_CLASSES => [],
//                  OrmConfigOptions::ENTITY_LISTENER_RESOLVER => DefaultEntityListenerResolver::class,
//                  OrmConfigOptions::TYPED_FIELD_MAPPER => DefaultTypedFieldMapper::class,
//                  OrmConfigOptions::FETCH_MODE_SUB_SELECT_BATCH_SIZE => 100,
//                  OrmConfigOptions::REPOSITORY_FACTORY => DefaultRepositoryFactory::class,
//                  OrmConfigOptions::DEFAULT_QUERY_HINTS => [
//                      Query::HINT_CUSTOM_OUTPUT_WALKER => Query\SqlWalker::class,
//                   ],
                    OrmConfigOptions::MAPPINGS => [
                        'User' => [
                            OrmConfigOptions::MAPPING_DIR => '@src/User/Entity',
                            OrmConfigOptions::MAPPING_DRIVER => DriverMappingEnum::ATTRIBUTE_MAPPING,
                            OrmConfigOptions::MAPPING_NAMESPACE => 'App\User\Entity',
                        ],
                    ],
                    OrmConfigOptions::DQL => [
                        OrmConfigOptions::DQL_CUSTOM_DATETIME_FUNCTIONS => [
//                            'ADDTIME' => AddTime::class
                        ],
                        OrmConfigOptions::DQL_CUSTOM_NUMERIC_FUNCTIONS => [
//                            'CEIL' => Ceil::class
                        ],
                        OrmConfigOptions::DQL_CUSTOM_STRING_FUNCTIONS => [
//                            'MD5' => Md5::class
                        ],
                    ],
                    OrmConfigOptions::CUSTOM_HYDRATION_MODES => [
                        // HydrationMode::class
                    ],
                    OrmConfigOptions::EVENTS => [
                        OrmConfigOptions::EVENTS_LISTENERS => [
                            Events::preFlush => [
//                            EventOrmListener::class
                            ],
                        ],
                        OrmConfigOptions::EVENTS_SUBSCRIBERS => [
//                        EventOrmSubscriber::class,
                        ]
                    ],
                    OrmConfigOptions::FILTERS => [
                        // Filter::class,
                    ],
                ],
                'mysql' => [
                    OrmConfigOptions::CONNECTION => 'mysql',
                    OrmConfigOptions::MAPPINGS => [
                        'User' => [
                            OrmConfigOptions::MAPPING_DIR => '@src/Mysql/User/Entity',
                            OrmConfigOptions::MAPPING_DRIVER => DriverMappingEnum::ATTRIBUTE_MAPPING,
                            OrmConfigOptions::MAPPING_NAMESPACE => 'App\Mysql\User\Entity',
                        ],
                    ],
                ]
            ],
        ],
    ],
    // configuration params https://www.doctrine-project.org/projects/doctrine-migrations/en/3.6/reference/configuration.html#configuration
    'yiisoft/yii-doctrine-migrations' => [
        'default' => [
            MigrationConfigOptions::TABLE_STORAGE => [
                MigrationConfigOptions::TABLE_NAME => 'postgres_migration_versions',
                MigrationConfigOptions::VERSION_COLUMN_NAME => 'version',
                MigrationConfigOptions::VERSION_COLUMN_LENGTH => 1024,
                MigrationConfigOptions::EXECUTED_AT_COLUMN_NAME => 'executed_at',
                MigrationConfigOptions::EXECUTION_TIME_COLUMN_NAME => 'execution_time',
            ],
            MigrationConfigOptions::MIGRATIONS_PATHS => [
                'App\Migrations\Postgres' => '@src/Migrations/Postgres',
            ],
            MigrationConfigOptions::ALL_OR_NOTHING => true,
            MigrationConfigOptions::CHECK_DATABASE_PLATFORM => true,
            // if using only dbal
//        MigrationConfigOptions::CONNECTION => 'default',
            // if using only orm entity manager
            MigrationConfigOptions::EM => 'default',
        ],
        'mysql' => [
            MigrationConfigOptions::TABLE_STORAGE => [
                MigrationConfigOptions::TABLE_NAME => 'mysql_migration_versions',
                MigrationConfigOptions::VERSION_COLUMN_NAME => 'version',
                MigrationConfigOptions::VERSION_COLUMN_LENGTH => 1024,
                MigrationConfigOptions::EXECUTED_AT_COLUMN_NAME => 'executed_at',
                MigrationConfigOptions::EXECUTION_TIME_COLUMN_NAME => 'execution_time',
            ],
            MigrationConfigOptions::MIGRATIONS_PATHS => [
                'App\Migrations\Mysql' => '@src/Migrations/Mysql',
            ],
            MigrationConfigOptions::ALL_OR_NOTHING => true,
            MigrationConfigOptions::CHECK_DATABASE_PLATFORM => true,
            // if using only dbal
//        'connection' => 'mysql',
            // if using only orm entity manager
            MigrationConfigOptions::EM => 'mysql',
        ],
    ],
];
