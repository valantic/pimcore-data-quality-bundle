<?php

namespace Valantic\DataQualityBundle\Tests\Service;

use Pimcore\Model\User;
use Valantic\DataQualityBundle\Service\CacheService;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;

class CacheServiceTest extends AbstractTestCase
{
    protected User $user;

    protected function setUp(): void
    {
        $this->user = (new User())
            ->setId(1)
            ->setLanguage('en');
    }

    public function testCacheKey(): void
    {
        $objectId = 2;
        $cacheKey = CacheService::getCacheKey($objectId);
        $this->assertSame($cacheKey, sprintf('%s_%d', CacheService::DATA_QUALITY_CACHE_KEY, $objectId));
    }

    public function testCacheTags(): void
    {
        $objectId = 2;
        $className = 'Product';
        $cacheTags = CacheService::getTags($objectId, $className);
        $this->assertSame(
            $cacheTags,
            [
                CacheService::DATA_QUALITY_CACHE_KEY,
                sprintf('%s_%d', CacheService::DATA_QUALITY_CACHE_KEY, $objectId),
                sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, $className),
            ]
        );
    }

    public function testUserCacheKey(): void
    {
        $objectId = 3;
        $cacheKey = CacheService::getCacheKey($objectId, $this->user);
        $this->assertSame($cacheKey, sprintf('%s_%d_user_%d', CacheService::DATA_QUALITY_CACHE_KEY, $objectId, $this->user->getId()));
    }

    public function testUserCacheTags(): void
    {
        $objectId = 4;
        $className = 'Product';
        $cacheTags = CacheService::getTags($objectId, $className, $this->user);
        $this->assertSame(
            $cacheTags,
            [
                CacheService::DATA_QUALITY_CACHE_KEY,
                sprintf('%s_%d', CacheService::DATA_QUALITY_CACHE_KEY, $objectId),
                sprintf('%s_%s', CacheService::DATA_QUALITY_CACHE_KEY, $className),
                CacheService::DATA_QUALITY_USER_TAG_KEY,
                sprintf('%s_%d', CacheService::DATA_QUALITY_USER_TAG_KEY, $this->user->getId()),
            ]
        );
    }
}
