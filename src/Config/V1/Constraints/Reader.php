<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1\Constraints;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Config\V1\AbstractReader;

class Reader extends AbstractReader implements ConstraintKeys
{
    /**
     * Given $obj, return the corresponding rules for $attribute.
     */
    public function getRulesForObjectAttribute(Concrete $obj, string $attribute): array
    {
        return $this->getRulesForClassAttribute($obj->getClassName(), $attribute);
    }

    /**
     * Given a class name, return the corresponding rules for $attribute.
     *
     * @param string $className Base name or ::class
     */
    public function getRulesForClassAttribute(string $className, string $attribute): array
    {
        $attributeConfig = $this->getForClassAttribute($className, $attribute);

        return $this->safeArray($attributeConfig, self::KEY_RULES);
    }

    /**
     * Given $obj, return the corresponding note for $attribute.
     */
    public function getNoteForObjectAttribute(Concrete $obj, string $attribute): ?string
    {
        return $this->getNoteForClassAttribute($obj->getClassName(), $attribute);
    }

    /**
     * Given a class name, return the corresponding note for $attribute.
     *
     * @param string $className Base name or ::class
     */
    public function getNoteForClassAttribute(string $className, string $attribute): ?string
    {
        $attributeConfig = $this->getForClassAttribute($className, $attribute);

        return $attributeConfig[self::KEY_NOTE] ?? null;
    }

    /**
     * Get the list of attributes of a class than can be validated i.e. are configured.
     */
    public function getConfiguredClassAttributes(string $classname): array
    {
        return array_keys($this->getForClass($classname));
    }

    /**
     * Checks whether $attributeName in $className is configured.
     */
    public function isClassAttributeConfigured(string $className, string $attributeName): bool
    {
        return in_array($attributeName, $this->getConfiguredClassAttributes($className), true);
    }

    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_CONSTRAINTS;
    }

    /**
     * Given $obj, return the corresponding config for $attribute.
     */
    protected function getForObjectAttribute(Concrete $obj, string $attribute): array
    {
        return $this->getForClassAttribute($obj->getClassName(), $attribute);
    }

    /**
     * Given a class name, return the corresponding config for $attribute.
     *
     * @param string $className Base name or ::class
     */
    protected function getForClassAttribute(string $className, string $attribute): array
    {
        $classConfig = $this->getForClass($className);

        return $this->safeArray($classConfig, $attribute);
    }
}
