<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Symfony\Component\Yaml\Yaml;

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

        $yaml = Yaml::dump($raw);

        return (bool)file_put_contents($this->getConfigFilePath(), $yaml);
    }
}
