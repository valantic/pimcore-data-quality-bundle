<?php

namespace Valantic\DataQualityBundle\Config\V1;

abstract class Config
{
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
