<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Symfony\Component\Yaml\Yaml;
use Throwable;

class Writer extends Config
{
    /**
     * @var Reader
     */
    protected $reader;

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
        if (in_array($attributeName, $this->reader->getConfiguredClassAttributes($className), true)) {
            return true;
        }

        $raw = $this->reader->getRaw();
        if (!in_array($className, $this->reader->getConfiguredClasses(), true)) {
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
        if (!in_array($attributeName, $this->reader->getConfiguredClassAttributes($className), true)) {
            return true;
        }

        $raw = $this->reader->getRaw();
        if (!in_array($className, $this->reader->getConfiguredClasses(), true)) {
            return true;
        }
        unset($raw[$className][$attributeName]);

        return $this->writeConfig($raw);
    }

    /**
     * Adds a new config entry for a class-attribute constraint if it does not yet exist.
     *
     * @param string $className
     * @param string $attributeName
     * @param string $constraint
     * @param string $params
     * @return bool
     */
    public function addConstraint($className, $attributeName, $constraint, $params = ''): bool
    {
        try {
            $paramsParsed = json_decode($params, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $throwable) {
            $paramsParsed = null;
        }

        $raw = $this->reader->getRaw();

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
    public function deleteConstraint($className, $attributeName, $constraint): bool
    {
        if (!in_array($attributeName, $this->reader->getConfiguredClassAttributes($className), true)) {
            return true;
        }

        $raw = $this->reader->getRaw();
        if (!in_array($className, $this->reader->getConfiguredClasses(), true)) {
            return true;
        }

        unset($raw[$className][$attributeName][$constraint]);

        return $this->writeConfig($raw);
    }

    /**
     * Persists the new config to disk.
     * @param array $raw The new config.
     * @return bool
     */
    protected function writeConfig(array $raw): bool
    {
        try {
            $yaml = Yaml::dump($raw, 4, 2, Yaml::DUMP_NULL_AS_TILDE);

            return (bool)file_put_contents($this->getConfigFilePath(), $yaml);
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
