<?php

namespace Valantic\DataQualityBundle\Config\V1\Meta;

use Valantic\DataQualityBundle\Config\V1\AbstractWriter;

class Writer extends AbstractWriter
{
    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_META;
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
     * Updates (or creates) a config entry for $className.
     *
     * @param string $className
     * @param array $locales
     * @param int $thresholdGreen
     * @param int $thresholdOrange
     * @return bool
     */
    public function addOrUpdate(string $className, array $locales = [], int $thresholdGreen = 0, int $thresholdOrange = 0): bool
    {
        $raw = $this->reader->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            $raw[$className] = [];
        }
        $raw[$className]['locales'] = $locales;
        $raw[$className]['threshold_green'] = $thresholdGreen / 100;
        $raw[$className]['threshold_orange'] = $thresholdOrange / 100;

        return $this->writeConfig($raw);
    }

    /**
     * Delete the config entry for $className.
     * @param string $className
     * @return bool
     */
    public function delete(string $className): bool
    {
        $raw = $this->reader->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            return true;
        }

        unset($raw[$className]);

        return $this->writeConfig($raw);
    }
}
