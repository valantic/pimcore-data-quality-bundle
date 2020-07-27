<?php

namespace Valantic\DataQualityBundle\Tests\Shared;

use Valantic\DataQualityBundle\Shared\SafeArray;

class SafeArrayImplementation
{
    use SafeArray;

    /**
     * @param mixed $arr
     * @param mixed $key
     *
     * @return array
     */
    public function get($arr, $key): array
    {
        return $this->safeArray($arr, $key);
    }
}
