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

    public function addOrUpdate(string $className, array $locales = []): bool
    {
        $raw = $this->reader->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            $raw[$className] = [];
        }
        $raw[$className] = $locales;

        return $this->writeConfig($raw);
    }

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
