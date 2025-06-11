<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Cache\Enum;

final class ConfigOptions
{
    public const CACHE = 'cache';
    public const DRIVER = 'driver';
    public const HOST = 'host';
    public const NAMESPACE = 'namespace';
    public const PATH = 'path';
    public const PORT = 'port';
    public const SERVER = 'server';

    private function __construct()
    {
    }
}
