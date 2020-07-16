<?php

namespace Valantic\DataQualityBundle\Tests\Shared;

use Valantic\DataQualityBundle\Shared\SafeArray;

class SafeArrayImplementation
{
    use SafeArray;

    public function get($arr, $key)
    {
        return $this->safeArray($arr, $key);
    }
}
