<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Orm\Factory;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\TypedFieldMapper;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\Persistence\Mapping\Driver\PHPDriver;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Doctrine\Cache\CacheCollector;
use Yiisoft\Yii\Doctrine\Orm\Enum\ConfigOptions;
use Yiisoft\Yii\Doctrine\Orm\Enum\DriverMappingEnum;

use function sprintf;

final class ConfigurationFactory
{
    public function __construct(
        private readonly Aliases $aliases,
        private readonly CacheCollector $cacheCollector,
        private readonly Injector $injector,
    ) {
    }

    /**
     * @psalm-param array{
     *     naming_strategy: class-string<NamingStrategy>|empty,
     *     quote_strategy: class-string<QuoteStrategy>|empty,
     *     schema_ignore_classes: list<class-string>|empty,
     *     fetch_mode_sub_select_batch_size: int,
     *     dql: array{
     *          custom_datetime_functions: array<string, callable(string):FunctionNode|class-string<FunctionNode>>|empty,
     *          custom_numeric_functions: array<string, callable(string):FunctionNode|class-string<FunctionNode>>|empty,
     *          custom_string_functions: array<string, callable(string):FunctionNode|class-string<FunctionNode>>|empty,
     *     }|null,
     *     class_metadata_factory_name: class-string<AbstractClassMetadataFactory>|empty,
     *     default_repository_class: class-string<EntityRepository<object>>|empty,
     *     repository_factory: class-string<RepositoryFactory>|empty,
     *     custom_hydration_modes: array<string, class-string<AbstractHydrator>>|empty,
     *     filters: array<string, class-string<SQLFilter>>|empty,
     *     entity_listener_resolver: class-string<EntityListenerResolver>|empty,
     *     typed_field_mapper: class-string<TypedFieldMapper>|empty,
     *     default_query_hints: array<string, class-string>|empty,
     *     mappings: array<string, array{
     *          dir: string,
     *          driver: object<DriverMappingEnum>,
     *          namespace: string,
     *          fileExtension: string|empty
     *     }>|empty,
     *     events: array|empty,
     *     connection: string
     * } $ormConfig
     * @psalm-param array{auto_generate: bool|null, path: string, namespace: string|null} $proxyConfig
     */
    public function create(array $ormConfig, array $proxyConfig): Configuration
    {
        $configuration = new Configuration();

        // naming strategy
        $this->configureNamingStrategy($configuration, $ormConfig[ConfigOptions::NAMING_STRATEGY] ?? null);
        // quote strategy
        $this->configureQuoteStrategy($configuration, $ormConfig[ConfigOptions::QUOTE_STRATEGY] ?? null);

        // orm cache
        $configuration->setHydrationCache($this->cacheCollector->getHydrationCache());
        $configuration->setMetadataCache($this->cacheCollector->getMetadataCache());
        $configuration->setQueryCache($this->cacheCollector->getQueryCache());
        $configuration->setResultCache($this->cacheCollector->getResultCache());

        // proxy
        $configuration->setProxyDir(
            $this->getProxyDir($proxyConfig[ConfigOptions::PROXY_PATH] ?? null),
        );
        $configuration->setProxyNamespace(
            $this->getProxyName($proxyConfig[ConfigOptions::PROXY_NAMESPACE] ?? 'Proxy'),
        );
        $configuration->setAutoGenerateProxyClasses($proxyConfig[ConfigOptions::PROXY_AUTO_GENERATE] ?? true);

        // configure schema ignore classes
        $this->configureSchemaIgnoreClasses($configuration, $ormConfig[ConfigOptions::SCHEMA_IGNORE_CLASSES] ?? []);

        // configure custom datetime function
        $this->configureCustomDatetimeFunctions(
            $configuration,
            $ormConfig[ConfigOptions::DQL][ConfigOptions::DQL_CUSTOM_DATETIME_FUNCTIONS] ?? [],
        );
        // configure custom numeric function
        $this->configureCustomNumericFunctions(
            $configuration,
            $ormConfig[ConfigOptions::DQL][ConfigOptions::DQL_CUSTOM_NUMERIC_FUNCTIONS] ?? [],
        );
        // configure custom string function
        $this->configureCustomStringFunctions(
            $configuration,
            $ormConfig[ConfigOptions::DQL][ConfigOptions::DQL_CUSTOM_STRING_FUNCTIONS] ?? [],
        );

        // configure class metadata factory
        $this->configureClassMetadataFactoryName(
            $configuration,
            $ormConfig[ConfigOptions::CLASS_METADATA_FACTORY_NAME] ?? null,
        );

        // configure default repository class
        $this->configureDefaultRepositoryClass(
            $configuration,
            $ormConfig[ConfigOptions::DEFAULT_REPOSITORY_CLASS] ?? null,
        );

        // configure repository factory
        $this->configureRepositoryFactory($configuration, $ormConfig[ConfigOptions::REPOSITORY_FACTORY] ?? null);

        // configure custom hydration modes
        $this->configureCustomHydrationModes($configuration, $ormConfig[ConfigOptions::CUSTOM_HYDRATION_MODES] ?? []);

        // configure filters
        $this->configureFilters($configuration, $ormConfig[ConfigOptions::FILTERS] ?? []);

        // configure entityListenerResolver
        $this->configureEntityListenerResolver(
            $configuration,
            $ormConfig[ConfigOptions::ENTITY_LISTENER_RESOLVER] ?? null,
        );

        // configure typed field mapper
        $this->configureTypedFieldMapper($configuration, $ormConfig[ConfigOptions::TYPED_FIELD_MAPPER] ?? null);

        // configure fetch mode sub select batch size
        $this->configureFetchModeSubselectBatchSize(
            $configuration,
            $ormConfig[ConfigOptions::FETCH_MODE_SUB_SELECT_BATCH_SIZE] ?? null,
        );

        // configure default query hints
        $this->configureDefaultQueryHints($configuration, $ormConfig[ConfigOptions::DEFAULT_QUERY_HINTS] ?? []);

        // configure meta data drivers
        $this->configureMetaDataDrivers($configuration, $ormConfig[ConfigOptions::MAPPINGS] ?? []);

        // configure identity generation preferences
        $this->configureIdentityGenerationPreferences(
            $configuration,
            $ormConfig[ConfigOptions::IDENTITY_GENERATION_PREFERENCES] ?? [],
        );

        return $configuration;
    }

    /**
     * @psalm-param class-string<AbstractClassMetadataFactory>|null $classMetadataFactoryName
     */
    private function configureClassMetadataFactoryName(
        Configuration $configuration,
        ?string $classMetadataFactoryName,
    ): void {
        if (null === $classMetadataFactoryName) {
            return;
        }

        $configuration->setClassMetadataFactoryName($classMetadataFactoryName);
    }

    /**
     * @psalm-param array<string, callable(string):FunctionNode|class-string<FunctionNode>>|empty $customDatetimeFunctions
     */
    private function configureCustomDatetimeFunctions(
        Configuration $configuration,
        array $customDatetimeFunctions,
    ): void {
        foreach ($customDatetimeFunctions as $name => $className) {
            $configuration->addCustomDatetimeFunction($name, $className);
        }
    }

    /**
     * @psalm-param array<string, class-string<AbstractHydrator>>|empty $customHydrationModes
     */
    private function configureCustomHydrationModes(Configuration $configuration, array $customHydrationModes): void
    {
        foreach ($customHydrationModes as $name => $className) {
            $configuration->addCustomHydrationMode($name, $className);
        }
    }

    /**
     * @psalm-param array<string, callable(string):FunctionNode|class-string<FunctionNode>>|empty $customNumericFunctions
     */
    private function configureCustomNumericFunctions(
        Configuration $configuration,
        array $customNumericFunctions,
    ): void {
        foreach ($customNumericFunctions as $name => $className) {
            $configuration->addCustomNumericFunction($name, $className);
        }
    }

    /**
     * @psalm-param array<string, callable(string):FunctionNode|class-string<FunctionNode>>|empty $customStringFunctions
     */
    private function configureCustomStringFunctions(
        Configuration $configuration,
        array $customStringFunctions,
    ): void {
        foreach ($customStringFunctions as $name => $className) {
            $configuration->addCustomStringFunction($name, $className);
        }
    }

    /**
     * @psalm-param array<string, class-string>|empty $defaultQueryHints
     */
    private function configureDefaultQueryHints(Configuration $configuration, array $defaultQueryHints): void
    {
        $configuration->setDefaultQueryHints($defaultQueryHints);
    }

    /**
     * @psalm-param class-string<EntityRepository>|null $defaultRepositoryClass
     */
    private function configureDefaultRepositoryClass(
        Configuration $configuration,
        ?string $defaultRepositoryClass,
    ): void {
        if (null !== $defaultRepositoryClass) {
            $configuration->setDefaultRepositoryClassName($defaultRepositoryClass);
        }
    }

    /**
     * @psalm-param class-string<EntityListenerResolver>|null $entityListenerResolverClass
     */
    private function configureEntityListenerResolver(
        Configuration $configuration,
        ?string $entityListenerResolverClass,
    ): void {
        if (null === $entityListenerResolverClass) {
            return;
        }

        $entityListenerResolver = $this->injector->make($entityListenerResolverClass);

        if (!$entityListenerResolver instanceof EntityListenerResolver) {
            throw new RuntimeException(
                sprintf('Class %s not instance %s', $entityListenerResolverClass, EntityListenerResolver::class)
            );
        }

        $configuration->setEntityListenerResolver($entityListenerResolver);
    }

    private function configureFetchModeSubselectBatchSize(
        Configuration $configuration,
        ?int $fetchModeSubselectBatchSize,
    ): void {
        if (null === $fetchModeSubselectBatchSize) {
            return;
        }

        $configuration->setEagerFetchBatchSize($fetchModeSubselectBatchSize);
    }

    /**
     * @psalm-param  array<string, class-string<SQLFilter>>|empty $filters
     */
    private function configureFilters(Configuration $configuration, array $filters): void
    {
        foreach ($filters as $name => $className) {
            $configuration->addFilter($name, $className);
        }
    }

    private function configureIdentityGenerationPreferences(
        Configuration $configuration,
        array $identityGenerationPreferences
    ): void {
        if (empty($identityGenerationPreferences)) {
            return;
        }

        $configuration->setIdentityGenerationPreferences($identityGenerationPreferences);
    }

    /**
     * @psalm-param array<string, array{
     *     dir: string,
     *     driver: object<DriverMappingEnum>,
     *     namespace: string,
     *     fileExtension:
     *     string|empty
     * }>|empty $mappings
     */
    private function configureMetaDataDrivers(Configuration $configuration, array $mappings): void
    {
        $driverChain = new MappingDriverChain();

        foreach ($mappings as $name => $mapper) {
            if (!isset($mapper[ConfigOptions::MAPPING_DRIVER])) {
                throw new InvalidArgumentException('Not found "driver" mapping');
            }

            if (!isset($mapper[ConfigOptions::MAPPING_DIR])) {
                throw new InvalidArgumentException('Not found "directory" mapping');
            }

            if (!isset($mapper[ConfigOptions::MAPPING_NAMESPACE])) {
                throw new InvalidArgumentException('Not found "namespace" mapping');
            }

            $dir = $this->aliases->get($mapper[ConfigOptions::MAPPING_DIR]);

            switch ($mapper[ConfigOptions::MAPPING_DRIVER]) {
                case DriverMappingEnum::XML_MAPPING:
                    $driver = new SimplifiedXmlDriver(
                        [
                            $dir => $mapper[ConfigOptions::MAPPING_NAMESPACE]
                        ],
                        $mapper[ConfigOptions::MAPPING_FILE_EXTENSION] ?? SimplifiedXmlDriver::DEFAULT_FILE_EXTENSION
                    );

                    break;
                case DriverMappingEnum::ATTRIBUTE_MAPPING:
                    $driver = new AttributeDriver([$dir]);

                    break;
                case DriverMappingEnum::PHP_MAPPING:
                    $driver = new PHPDriver([$dir]);

                    break;
                case DriverMappingEnum::STATIC_PHP_MAPPING:
                    $driver = new StaticPHPDriver([$dir]);

                    break;
                default:
                    throw new InvalidArgumentException(
                        'Doctrine driver mapper: "attribute", "php", "static_php", "xml" not found'
                    );
            }

            $driverChain->addDriver($driver, $mapper[ConfigOptions::MAPPING_NAMESPACE]);
        }

        $configuration->setMetadataDriverImpl($driverChain);
    }

    /**
     * @psalm-param class-string<NamingStrategy>|null $className
     */
    private function configureNamingStrategy(Configuration $configuration, ?string $className): void
    {
        if (null === $className) {
            return;
        }

        $namingStrategy = $this->injector->make($className);

        if (!$namingStrategy instanceof NamingStrategy) {
            throw new RuntimeException(sprintf('Class %s not instanceof %s', $className, NamingStrategy::class));
        }

        $configuration->setNamingStrategy($namingStrategy);
    }

    /**
     * @psalm-param class-string<QuoteStrategy>|null $className
     */
    private function configureQuoteStrategy(Configuration $configuration, ?string $className): void
    {
        if (null === $className) {
            return;
        }

        $quoteStrategy = $this->injector->make($className);

        if (!$quoteStrategy instanceof QuoteStrategy) {
            throw new RuntimeException(sprintf('Class %s not instanceof %s', $className, QuoteStrategy::class));
        }

        $configuration->setQuoteStrategy($quoteStrategy);
    }

    /**
     * @psalm-param class-string<RepositoryFactory>|null $repositoryFactoryClass
     */
    private function configureRepositoryFactory(Configuration $configuration, ?string $repositoryFactoryClass): void
    {
        if (null === $repositoryFactoryClass) {
            return;
        }

        $repositoryFactory = $this->injector->make($repositoryFactoryClass);

        if (!$repositoryFactory instanceof RepositoryFactory) {
            throw new RuntimeException(
                sprintf('Class %s not instance %s', $repositoryFactoryClass, RepositoryFactory::class)
            );
        }

        $configuration->setRepositoryFactory($repositoryFactory);
    }

    /**
     * @param list<class-string>|empty $schemaIgnoreClasses
     */
    private function configureSchemaIgnoreClasses(Configuration $configuration, array $schemaIgnoreClasses): void
    {
        if (count($schemaIgnoreClasses) > 0) {
            $configuration->setSchemaIgnoreClasses($schemaIgnoreClasses);
        }
    }

    /**
     * @psalm-param class-string<TypedFieldMapper>|null $typedFieldMapperClass
     */
    private function configureTypedFieldMapper(Configuration $configuration, ?string $typedFieldMapperClass): void
    {
        if (null === $typedFieldMapperClass) {
            return;
        }

        $typedFieldMapper = $this->injector->make($typedFieldMapperClass);

        if (!$typedFieldMapper instanceof TypedFieldMapper) {
            throw new RuntimeException(
                sprintf('Class %s not instance %s', $typedFieldMapperClass, EntityListenerResolver::class)
            );
        }

        $configuration->setTypedFieldMapper($typedFieldMapper);
    }

    private function getProxyDir(?string $proxyPath): string
    {
        if (null === $proxyPath) {
            throw new RuntimeException('Not found path proxies');
        }

        return $this->aliases->get($proxyPath);
    }

    private function getProxyName(?string $namespace): string
    {
        if (null === $namespace) {
            throw new InvalidArgumentException('Not found proxies namespace');
        }

        return $namespace;
    }
}
