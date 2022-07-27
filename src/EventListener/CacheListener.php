<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\EventListener;

use Pimcore\Event\Model\DataObjectEvent;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Service\CacheService;

class CacheListener
{
    protected static bool $isEnabled = true;

    public function __construct(
        protected TagAwareCacheInterface $cache,
        protected CacheService $cacheService,
        protected ConfigurationRepository $configurationRepository,
    ) {
    }

    public function handle(DataObjectEvent $event): void
    {
        if (!self::$isEnabled) {
            return;
        }

        $obj = $event->getObject();
        if (!$obj instanceof Concrete) {
            return;
        }

        $this->flushCache($obj);
    }

    public static function enableListener(): void
    {
        self::$isEnabled = true;
    }

    public static function disableListener(): void
    {
        self::$isEnabled = false;
    }

    protected function flushCache(Concrete $obj): void
    {
        $this->cache->invalidateTags($this->cacheService->getTags($obj));

        $this->doFlushCache(
            $obj,
            $obj,
            $this->configurationRepository->getConfiguredNestingLimit($obj::class)
        );
    }

    protected function doFlushCache(Concrete $obj, Concrete $root, int $currentNestingLevel): void
    {
        foreach ([...$obj->getDependencies()->getRequires(), ...$obj->getDependencies()->getRequiredBy()] as $requirement) {
            if ($requirement['type'] !== 'object') {
                continue;
            }
            $req = Concrete::getById($requirement['id']);

            if (!$req instanceof Concrete) {
                continue;
            }

            $this->cache->invalidateTags($this->cacheService->getTags($req));

            if ($currentNestingLevel <= $this->configurationRepository->getConfiguredNestingLimit($root::class)) {
                $this->doFlushCache($req, $root, $currentNestingLevel + 1);
            }
        }
    }
}
