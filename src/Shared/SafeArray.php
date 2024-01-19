<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Shared;

trait SafeArray
{
    /**
     * If $arr is an array and $key exists as array key, $arr[$key] is returned.
     * If one of these conditions is not met, an empty array is returned.
     *
     * This method does not have any type hints on purpose.
     *
     * @param array|mixed $arr
     *
     * @return array always returns an array, defaults to []
     */
    protected function safeArray(mixed $arr, string|int|null $key): array
    {
        if ($key === null) {
            return [];
        }
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
