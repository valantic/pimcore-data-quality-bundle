<?php

namespace Valantic\DataQualityBundle\Service;

use Pimcore\Cache;
use Pimcore\Model\User as PimcoreUser;
use Valantic\DataQualityBundle\Shared\ClassBasenameTrait;

class CacheService
{
    use ClassBasenameTrait;

    public const DATA_QUALITY_CACHE_KEY = 'valantic_dataquality_object';
    public const DATA_QUALITY_SCORE_CACHE_KEY = 'valantic_dataquality_object_score';
    public const DATA_QUALITY_USER_TAG_KEY = 'valantic_dataquality_user';

    public function clearTag(string $tag): void
    {
        Cache::clearTag($tag);
    }

    public static function getScoreCacheKey(int $id): string
    {
        return sprintf('%s_%d', self::DATA_QUALITY_SCORE_CACHE_KEY, $id);
    }

    public static function getCacheKey(int $id, ?PimcoreUser $user = null): string
    {
        if ($user !== null && $user->getId() !== null) {
            return sprintf('%s_%d_user_%d', self::DATA_QUALITY_CACHE_KEY, $id, $user->getId());
        }

        return sprintf('%s_%d', self::DATA_QUALITY_CACHE_KEY, $id);
    }

    public static function getTags(int $id, string $className, ?PimcoreUser $user = null): array
    {
        $tags = [
            self::DATA_QUALITY_CACHE_KEY,
            sprintf('%s_%d', self::DATA_QUALITY_CACHE_KEY, $id),
            sprintf('%s_%s', self::DATA_QUALITY_CACHE_KEY, self::classBasename($className)),
        ];

        if ($user !== null && $user->getId() !== null) {
            $tags[] = self::DATA_QUALITY_USER_TAG_KEY;
            $tags[] = sprintf('%s_%d', self::DATA_QUALITY_USER_TAG_KEY, $user->getId());
        }

        return $tags;
    }
}
