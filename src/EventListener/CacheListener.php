<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\EventListener;

use Pimcore\Cache;
use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Event\Model\UserRoleEvent;
use Pimcore\Model\DataObject;
use Pimcore\Model\User as PimcoreUser;
use Valantic\DataQualityBundle\Service\CacheService;

class CacheListener
{
    public function handleObject(DataObjectEvent $event): void
    {
        if ($event->hasArgument('saveVersionOnly') && $event->getArgument('saveVersionOnly') === true) {
            return;
        }

        $obj = $event->getObject();
        if (!$obj instanceof DataObject\Concrete) {
            return;
        }

        Cache::clearTag(sprintf('%s_%d', CacheService::DATA_QUALITY_CACHE_KEY, $obj->getId()));
    }

    public function handleUser(UserRoleEvent $event): void
    {
        $userRole = $event->getUserRole();
        if ($userRole instanceof PimcoreUser) {
            Cache::clearTag(sprintf('%s_%d', CacheService::DATA_QUALITY_USER_TAG_KEY, $userRole->getId()));

            return;
        }

        Cache::clearTag(CacheService::DATA_QUALITY_USER_TAG_KEY);
    }
}
