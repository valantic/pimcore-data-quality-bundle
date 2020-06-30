<?php

namespace Valantic\DataQualityBundle\Config\V1\Constraints;

use Throwable;
use Valantic\DataQualityBundle\Config\V1\AbstractWriter;

class Writer extends AbstractWriter
{
    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_CONSTRAINTS;
    }

    /**
     * Write the bundle's config file.
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    /**
     * Adds a new config entry for a class-attribute combination if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     * @return bool
     */
    public function addClassAttribute(string $className, string $attributeName): bool
    {
        if ($this->reader->isClassAttributeConfigured($className, $attributeName)) {
            return true;
        }

        $raw = $this->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            $raw[$className] = [];
        }
        $raw[$className][$attributeName] = [];

        return $this->writeConfig($raw);
    }


    /**
     * Adds a new config entry for a class-attribute combination if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     * @return bool
     */
    public function removeClassAttribute(string $className, string $attributeName): bool
    {
        if (!$this->reader->isClassAttributeConfigured($className, $attributeName)) {
            return true;
        }

        $raw = $this->reader->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            return true;
        }
        unset($raw[$className][$attributeName]);

        return $this->writeConfig($raw);
    }

    /**
     * Adds a new config entry or edits an existing one for a class-attribute constraint if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     * @param string $constraint
     * @param string $params
     * @return bool
     */
    public function addOrModifyConstraint(string $className, string $attributeName, string $constraint, string $params = null): bool
    {
        try {
            $paramsParsed = json_decode($params, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            $paramsParsed = $params;
        }

        if($paramsParsed === ''){
            $paramsParsed=null;
        }

        $raw = $this->reader->getCurrentSection();

        $raw[$className][$attributeName][$constraint] = $paramsParsed;

        return $this->writeConfig($raw);
    }

    /**
     * Adds a new config entry for a class-attribute constraint if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     * @param string $constraint
     * @return bool
     */
    public function deleteConstraint(string $className, string $attributeName, string $constraint): bool
    {
        if (!$this->reader->isClassAttributeConfigured($className, $attributeName)) {
            return true;
        }

        $raw = $this->reader->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            return true;
        }

        unset($raw[$className][$attributeName][$constraint]);

        return $this->writeConfig($raw);
    }
}
