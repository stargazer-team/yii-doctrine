<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Dbal\Enum;

final class ConfigOptions
{
    public const AUTO_COMMIT = 'auto_commit';

    public const CONNECTIONS = 'connections';

    public const CUSTOM_TYPES = 'custom_types';

    public const DISABLE_TYPE_COMMENTS = 'disable_type_comments';

    public const DBAL = 'dbal';

    public const MAPPING_TYPES = 'mapping_types';

    public const MIDDLEWARES = 'middlewares';

    public const PARAMS = 'params';

    public const SCHEMA_ASSETS_FILTER = 'schema_assets_filter';

    public const SCHEMA_MANAGER_FACTORY = 'schema_manager_factory';

    private function __construct()
    {
    }
}
