<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Symfony\Component\Yaml\Exception\ExceptionInterface as YamlException;
use Symfony\Component\Yaml\Yaml;
use Valantic\DataQualityBundle\Event\InvalidConfigEvent;
use Valantic\DataQualityBundle\Shared\SafeArray;

abstract class Config
{
    use SafeArray;

    protected const CONFIG_SECTION_CONSTRAINTS = 'constraints';

    protected const CONFIG_SECTION_META = 'meta';

    /**
     * The identifier (a const starting with CONFIG_SECTION_) for the current config section.
     * @return string
     */
    abstract protected function getCurrentSectionName(): string;

    /**
     * Returns the absolute path to the config file.
     *
     * @return string
     */
    protected function getConfigFilePath(): string
    {
        return PIMCORE_CONFIGURATION_DIRECTORY . '/valantic_dataquality_config.yml';
    }

    /**
     * Returns the raw config as read from disk.
     *
     * @return array
     * @internal
     */
    protected function getRaw(): array
    {
        try {
            $parsed = Yaml::parseFile($this->getConfigFilePath());
        } catch (YamlException $exception) {
            $this->eventDispatcher->dispatch(new InvalidConfigEvent());

            return [];
        }

        if (!is_array($parsed)) {
            $this->eventDispatcher->dispatch(new InvalidConfigEvent());

            return [];
        }

        /**
         * @var $parsed array
         */
        return $parsed;
    }

    /**
     * Get a config section.
     *
     * @param string $name
     * @return array
     */
    private function getSection(string $name): array
    {
        return array_key_exists($name, $this->getRaw()) && $this->getRaw()[$name]
            ? $this->getRaw()[$name]
            : [];
    }

    /**
     * Default method to get the current section inside a config class.
     * @return array
     */
    protected function getCurrentSection(): array
    {
        return $this->getSection($this->getCurrentSectionName());
    }

}
