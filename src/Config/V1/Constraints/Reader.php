<?php

namespace Valantic\DataQualityBundle\Config\V1\Constraints;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Config\V1\AbstractReader;

class Reader extends AbstractReader
{
    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_CONSTRAINTS;
    }

    /**
     * @param Concrete $obj
     * @param string $attribute
     * @return array Given $obj, return the corresponding config for $attribute;
     */
    public function getForObjectAttribute(Concrete $obj, string $attribute): array
    {
        return $this->getForClassAttribute($obj->getClassName(), $attribute);
    }

    /**
     * Get the list of attributes of a class than can be validated i.e. are configured.
     *
     * @param string $classname
     *
     * @return array
     */
    public function getConfiguredClassAttributes(string $classname): array
    {
        return array_keys($this->getForClass($classname));
    }

    /**
     * Given a class name, return the corresponding config for $attribute.
     *
     * @param string $className Base name or ::class
     * @param string $attribute
     * @return array
     */
    public function getForClassAttribute(string $className, string $attribute): array
    {
        $classConfig = $this->getForClass($className);

        return $this->safeArray($classConfig, $attribute);
    }

    /**
     * Checks whether $attributeName in $className is configured.
     * @param string $className
     * @param string $attributeName
     * @return bool
     */
    public function isClassAttributeConfigured(string $className, string $attributeName): bool
    {
        return in_array($attributeName, $this->getConfiguredClassAttributes($className), true);
    }
}
