<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Migrations\Enum;

final class ConfigOptions
{
    public const ALL_OR_NOTHING = 'all_or_nothing';

    public const CHECK_DATABASE_PLATFORM = 'check_database_platform';

    public const CONNECTION = 'connection';

    public const EM = 'em';

    public const EXECUTED_AT_COLUMN_NAME = 'executed_at_column_name';
    public const EXECUTION_TIME_COLUMN_NAME = 'execution_time_column_name';
    public const MIGRATIONS_PATHS = 'migrations_paths';

    public const TABLE_NAME = 'table_name';

    public const TABLE_STORAGE = 'table_storage';

    public const VERSION_COLUMN_LENGTH = 'version_column_length';

    public const VERSION_COLUMN_NAME = 'version_column_name';

    private function __construct()
    {
    }
}
