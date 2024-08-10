<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Orm\Enum;

final class ConfigOptions
{
    public const CLASS_METADATA_FACTORY_NAME = 'class_metadata_factory_name';

    public const CONNECTION = 'connection';

    public const CUSTOM_HYDRATION_MODES = 'custom_hydration_modes';

    public const DEFAULT_ENTITY_MANAGER = 'default_entity_manager';

    public const DEFAULT_QUERY_HINTS = 'default_query_hints';

    public const DEFAULT_REPOSITORY_CLASS = 'default_repository_class';

    public const DQL = 'dql';

    public const DQL_CUSTOM_DATETIME_FUNCTIONS = 'custom_datetime_functions';

    public const DQL_CUSTOM_NUMERIC_FUNCTIONS = 'custom_numeric_functions';

    public const DQL_CUSTOM_STRING_FUNCTIONS = 'custom_string_functions';

    public const ENTITY_LISTENER_RESOLVER = 'entity_listener_resolver';

    public const ENTITY_MANAGERS = 'entity_managers';

    public const EVENTS = 'events';

    public const EVENTS_LISTENERS = 'listeners';

    public const EVENTS_SUBSCRIBERS = 'subscribers';

    public const FETCH_MODE_SUB_SELECT_BATCH_SIZE = 'fetch_mode_sub_select_batch_size';

    public const FILTERS = 'filters';

    public const MAPPINGS = 'mappings';

    public const MAPPING_DIR = 'dir';

    public const MAPPING_DRIVER = 'driver';

    public const MAPPING_FILE_EXTENSION = 'file_extension';

    public const MAPPING_NAMESPACE = 'namespace';

    public const NAMING_STRATEGY = 'naming_strategy';

    public const ORM = 'orm';

    public const PROXIES = 'proxies';

    public const PROXY_AUTO_GENERATE = 'auto_generate';

    public const PROXY_NAMESPACE = 'namespace';

    public const PROXY_PATH = 'path';

    public const QUOTE_STRATEGY = 'quote_strategy';

    public const REPOSITORY_FACTORY = 'repository_factory';

    public const SCHEMA_IGNORE_CLASSES = 'schema_ignore_classes';

    public const TYPED_FIELD_MAPPER = 'typed_field_mapper';

    private function __construct()
    {
    }
}
