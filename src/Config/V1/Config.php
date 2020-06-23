<?php

namespace Valantic\DataQualityBundle\Config\V1;

use Symfony\Component\Yaml\Exception\ExceptionInterface as YamlException;
use Symfony\Component\Yaml\Yaml;
use Valantic\DataQualityBundle\Event\InvalidConfigEvent;

abstract class Config
{
    protected const CONFIG_SECTION_CONSTRAINTS = 'constraints';

    protected const CONFIG_SECTION_LOCALES = 'locales';

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

    /**
     * If $arr is an array and $key exists as array key, $arr[$key] is returned.
     * If one of these conditions is not met, an empty array is returned.
     *
     * This method does not have any type hints on purpose.
     *
     * @param $arr
     * @param $key
     * @return array Always returns an array, defaults to [].
     */
    protected function safeArray($arr, $key): array
    {
        if (!is_array($arr)) {
            return [];
        }

        if (!array_key_exists($key, $arr)) {
            return [];
        }

        $subArr = $arr[$key];

        if (!is_array($subArr)) {
            return [];
        }

        return $subArr;
    }
}
