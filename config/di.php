<?php

declare(strict_types=1);

use Yiisoft\Definitions\Reference;
use Yiisoft\Yii\Doctrine\Cache\CacheCollector;
use Yiisoft\Yii\Doctrine\DoctrineManager;
use Yiisoft\Yii\Doctrine\Factory\DoctrineManagerFactory;

/** @var array $params */

return [
    DoctrineManager::class => [
        'definition' => static fn(
            DoctrineManagerFactory $doctrineManagerFactory
        ): DoctrineManager => $doctrineManagerFactory->create($params['yiisoft/yii-doctrine'] ?? []),
        'reset' => function (): void {
            /** @var DoctrineManager $this */
            $this->resetAllManager();
        },
    ],

    CacheCollector::class => [
        'class' => CacheCollector::class,
        '__construct()' => [
            'hydrationCache' => Reference::optional(CacheCollector::DOCTRINE_HYDRATION_CACHE),
            'metadataCache' => Reference::optional(CacheCollector::DOCTRINE_METADATA_CACHE),
            'queryCache' => Reference::optional(CacheCollector::DOCTRINE_QUERY_CACHE),
            'resultCache' => Reference::optional(CacheCollector::DOCTRINE_RESULT_CACHE),
        ]
    ],
];
