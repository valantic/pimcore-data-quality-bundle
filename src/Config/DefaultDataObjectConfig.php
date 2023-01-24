<?php

namespace Valantic\DataQualityBundle\Config;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;

final class DefaultDataObjectConfig extends AbstractDataObjectConfig
{
    public function __construct(protected ConfigurationRepository $configurationRepository)
    {
    }

    public function getClass(): string
    {
        return Concrete::class;
    }

    public function getLocales(Concrete $obj): array
    {
        return $this->configurationRepository->getConfiguredLocales($obj::class);
    }

    public function getIgnoreFallbackLanguage(Concrete $obj): bool
    {
        return false;
    }

    public static function isDefault(): bool
    {
        return true;
    }
}
