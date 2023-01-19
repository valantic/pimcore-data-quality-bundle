<?php

namespace Valantic\DataQualityBundle\Config;

use Pimcore\Model\DataObject\Concrete;

interface DataObjectConfigInterface
{
    public const VALIDATION_GROUP_DEFAULT = 'default';

    /** @return class-string */
    public function getClass(): string;

    public function getValidationGroups(Concrete $obj): array;

    /** @return string[] */
    public function getLocales(Concrete $obj): array;

    public static function isDefault(): bool;

    public function getIgnoreFallbackLanguage(Concrete $obj): bool;
}
