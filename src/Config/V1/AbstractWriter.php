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

    public function ensureConfigExists(): bool
    {
        try {
            return touch($this->getConfigFilePath());
        } catch (Throwable $throwable) {
            return false;
        }
    }
}
