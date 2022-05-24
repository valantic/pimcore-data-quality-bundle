<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1;

use Symfony\Component\Yaml\Yaml;
use Throwable;

abstract class AbstractWriter extends Config implements WriterInterface
{
    /**
     * Ensures the config file exists.
     */
    public function ensureConfigExists(): bool
    {
        try {
            return touch($this->getConfigFilePath());
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Persists the new config to disk.
     *
     * @param array $updated the new config
     */
    protected function writeConfig(array $updated): bool
    {
        try {
            $raw = $this->getRaw();
            $raw[$this->getCurrentSectionName()] = $updated;
            $yaml = Yaml::dump($raw, 5, 2, Yaml::DUMP_NULL_AS_TILDE);

            return (bool) file_put_contents($this->getConfigFilePath(), $yaml);
        } catch (Throwable) {
            return false;
        }
    }
}
