<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Dbal\Enum;

final class ConfigOptions
{
    public const AUTO_COMMIT = 'auto_commit';

    public const CUSTOM_TYPES = 'custom_types';

    public const DBAL = 'dbal';

    public const MIDDLEWARES = 'middlewares';

    public const PARAMS = 'params';

    public const SCHEMA_ASSETS_FILTER = 'schema_assets_filter';

    private function __construct()
    {
    }
}
