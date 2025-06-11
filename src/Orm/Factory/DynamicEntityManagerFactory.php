<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Orm\Factory;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Internal\Hydration\AbstractHydrator;
use Doctrine\ORM\Mapping\EntityListenerResolver;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\QuoteStrategy;
use Doctrine\ORM\Mapping\TypedFieldMapper;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Doctrine\ORM\Repository\RepositoryFactory;
use Doctrine\Persistence\Mapping\AbstractClassMetadataFactory;
use RuntimeException;
use Yiisoft\Yii\Doctrine\DoctrineManager;
use Yiisoft\Yii\Doctrine\Orm\Enum\ConfigOptions;
use Yiisoft\Yii\Doctrine\Orm\Enum\DriverMappingEnum;

use function sprintf;

final class DynamicEntityManagerFactory
{
    public function __construct(
        private readonly DoctrineManager $doctrineManager,
        private readonly EntityManagerFactory $entityManagerFactory,
    ) {
    }

    /**
     * @psalm-param array{
     *     naming_strategy?: class-string<NamingStrategy>,
     *     quote_strategy?: class-string<QuoteStrategy>,
     *     schema_ignore_classes?: list<class-string>,
     *     fetch_mode_sub_select_batch_size?: int,
     *     dql?: array{
     *          custom_datetime_functions: array<string, callable(string):FunctionNode|class-string<FunctionNode>>,
     *          custom_numeric_functions: array<string, callable(string):FunctionNode|class-string<FunctionNode>>,
     *          custom_string_functions: array<string, callable(string):FunctionNode|class-string<FunctionNode>>,
     *     },
     *     class_metadata_factory_name?: class-string<AbstractClassMetadataFactory>,
     *     default_repository_class?: class-string<EntityRepository<object>>,
     *     repository_factory?: class-string<RepositoryFactory>,
     *     custom_hydration_modes?: array<string, class-string<AbstractHydrator>>,
     *     filters?: array<string, class-string<SQLFilter>>,
     *     entity_listener_resolver?: class-string<EntityListenerResolver>,
     *     typed_field_mapper?: class-string<TypedFieldMapper>,
     *     default_query_hints?: array<string, class-string>,
     *     mappings: array<string, array{
     *          dir: string,
     *          driver: object<DriverMappingEnum>,
     *          namespace: string,
     *          fileExtension?: string
     *     }>|non-empty-array,
     *     events?: array,
     *     connection: string|non-empty-string
     * } $entityManagerConfig
     * @psalm-param array{auto_generate?: bool, path: string, namespace?: string}|empty $proxyConfig
     */
    public function create(
        array $entityManagerConfig,
        array $proxyConfig,
        string $entityManagerName,
    ): EntityManagerInterface {
        if ($this->doctrineManager->hasManager($entityManagerName)) {
            throw new RuntimeException(sprintf('Entity manager "%s" already exist', $entityManagerName));
        }

        $connection = $this->doctrineManager->getConnection($entityManagerConfig[ConfigOptions::CONNECTION]);

        $entityManager = $this->entityManagerFactory->create($connection, $entityManagerConfig, $proxyConfig);

        $this->doctrineManager->addManager($entityManagerName, $entityManager);

        return $entityManager;
    }
}
