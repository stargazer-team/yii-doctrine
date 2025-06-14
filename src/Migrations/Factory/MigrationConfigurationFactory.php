<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Migrations\Factory;

use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Doctrine\Migrations\Enum\ConfigOptions;
use Yiisoft\Yii\Doctrine\Migrations\MigrationConfigurationRegistry;

final class MigrationConfigurationFactory
{
    public function __construct(
        private readonly Aliases $aliases,
    ) {
    }

    /**
     * @psalm-param array<string, array{
     *     table_storage: array{
     *          table_name: string|empty,
     *          version_column_name: string|empty,
     *          version_column_length: int|empty,
     *          executed_at_column_name: string|empty
     *     }|empty,
     *     migrations_paths: array<string, string>,
     *     all_or_nothing: bool|empty,
     *     check_database_platform: bool|empty
     * }> $migrationConfig
     */
    public function create(array $migrationConfig): MigrationConfigurationRegistry
    {
        $configurations = [];

        if (!empty($migrationConfig)) {
            foreach ($migrationConfig as $configName => $config) {
                foreach ($config[ConfigOptions::MIGRATIONS_PATHS] as $namespace => $path) {
                    $migrationConfig[$configName][ConfigOptions::MIGRATIONS_PATHS][$namespace] = $this->aliases->get(
                        $path,
                    );
                }
            }

            foreach ($migrationConfig as $configName => $config) {
                // create configuration
                $configuration = (new ConfigurationArray($config))
                    ->getConfiguration();

                $configurations[$configName] = $configuration;
            }
        }

        return new MigrationConfigurationRegistry($configurations);
    }
}
