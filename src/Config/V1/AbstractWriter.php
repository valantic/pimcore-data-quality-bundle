<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Symfony\Component\Yaml\Yaml;
use Throwable;

abstract class AbstractWriter extends Config
{
    /**
     * @var AbstractReader
     */
    protected $reader;

    /**
     * Persists the new config to disk.
     * @param array $updated The new config.
     * @return bool
     */
    protected function writeConfig(array $updated): bool
    {
        try {
            $raw = $this->getRaw();
            $raw[$this->getCurrentSectionName()] = $updated;
            $yaml = Yaml::dump($raw, 5, 2, Yaml::DUMP_NULL_AS_TILDE);

            return (bool)file_put_contents($this->getConfigFilePath(), $yaml);
        } catch (Throwable $throwable) {
            return false;
        }
    }

    /**
     * Ensures the config file exists.
     * @return bool
     */
    public function ensureConfigExists(): bool
    {
        try {
            return touch($this->getConfigFilePath());
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
