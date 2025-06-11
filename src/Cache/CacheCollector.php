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

    private CacheItemPoolInterface $hydrationCache;

    private CacheItemPoolInterface $metadataCache;

    private CacheItemPoolInterface $queryCache;

    private CacheItemPoolInterface $resultCache;

    public function __construct(
        ?CacheItemPoolInterface $hydrationCache,
        ?CacheItemPoolInterface $metadataCache,
        ?CacheItemPoolInterface $queryCache,
        ?CacheItemPoolInterface $resultCache,
    ) {
        $this->hydrationCache = $hydrationCache ?? new NullAdapter();
        $this->metadataCache = $metadataCache ?? new NullAdapter();
        $this->queryCache = $queryCache ?? new NullAdapter();
        $this->resultCache = $resultCache ?? new NullAdapter();
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
