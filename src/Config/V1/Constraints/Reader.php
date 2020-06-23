<?php

namespace Valantic\DataQualityBundle\Config\V1\Constraints;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Yaml\Exception\ExceptionInterface as YamlException;
use Symfony\Component\Yaml\Yaml;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\AbstractReader;
use Valantic\DataQualityBundle\Config\V1\Config;
use Valantic\DataQualityBundle\Event\InvalidConfigEvent;
use Valantic\DataQualityBundle\Service\ClassInformation;

class Reader extends AbstractReader
{
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_CONSTRAINTS;
    }

    /**
     * Given $obj, return the corresponding config.
     *
     * @param Concrete $obj
     * @return array
     */
    public function getForObject(Concrete $obj): array
    {
        return $this->getForClass($obj->getClassName());
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
     * Get the list of classes than can be validated i.e. are configured.
     *
     * @return array
     */
    public function getConfiguredClasses(): array
    {
        return array_keys($this->getCurrentSection());
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
     * Given a class name, return the corresponding config.
     *
     * @param string $className Base name or ::class
     * @return array
     */
    public function getForClass(string $className): array
    {
        try {
            $className = (new ClassInformation($className))->getClassName();
        } catch (Throwable $throwable) {
            return [];
        }

        if (!in_array($className, $this->getConfiguredClasses(), true)) {
            return [];
        }

        return $this->safeArray($this->getCurrentSection(), $className);
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
}
