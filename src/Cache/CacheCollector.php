<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Doctrine\Cache;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\NullAdapter;

final class CacheCollector
{
    public const DOCTRINE_HYDRATION_CACHE = 'doctrine.hydration.cache';
    public const DOCTRINE_METADATA_CACHE = 'doctrine.metadata.cache';
    public const DOCTRINE_QUERY_CACHE = 'doctrine.query.cache';
    public const DOCTRINE_RESULT_CACHE = 'doctrine.result.cache';

    public function __construct(
        private readonly CacheItemPoolInterface $hydrationCache = new NullAdapter(),
        private readonly CacheItemPoolInterface $metadataCache = new NullAdapter(),
        private readonly CacheItemPoolInterface $queryCache = new NullAdapter(),
        private readonly CacheItemPoolInterface $resultCache = new NullAdapter(),
    ) {
    }

    public function getHydrationCache(): CacheItemPoolInterface
    {
        return $this->hydrationCache;
    }

    public function getMetadataCache(): CacheItemPoolInterface
    {
        return $this->metadataCache;
    }

    public function getQueryCache(): CacheItemPoolInterface
    {
        return $this->queryCache;
    }

    public function getResultCache(): CacheItemPoolInterface
    {
        return $this->resultCache;
    }
}
