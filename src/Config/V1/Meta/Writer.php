<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1\Meta;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Valantic\DataQualityBundle\Config\V1\AbstractWriter;

class Writer extends AbstractWriter implements MetaKeys
{
    /**
     * Write the bundle's config file.
     *
     * @param Reader $reader
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(protected Reader $reader, protected EventDispatcherInterface $eventDispatcher)
    {
    }

    /**
     * Updates (or creates) a config entry for $className.
     */
    public function update(string $className, array $locales = [], int $thresholdGreen = 0, int $thresholdOrange = 0, int $nestingLimit = 1): bool
    {
        $raw = $this->reader->getCurrentSection();
        if (!$this->reader->isClassConfigured($className)) {
            $raw[$className] = [];
        }
        $raw[$className][self::KEY_LOCALES] = $locales;
        $raw[$className][self::KEY_THRESHOLD_GREEN] = $thresholdGreen / 100;
        $raw[$className][self::KEY_THRESHOLD_ORANGE] = $thresholdOrange / 100;
        $raw[$className][self::KEY_NESTING_LIMIT] = $nestingLimit;

        return $this->writeConfig($raw);
    }

    /**
     * Delete the config entry for $className.
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

    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_META;
    }
}
