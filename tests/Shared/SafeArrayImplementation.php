<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Shared;

use Valantic\DataQualityBundle\Shared\SafeArray;

class SafeArrayImplementation
{
    use SafeArray;

    public function get(mixed $arr, int|string|null $key): array
    {
        return $this->safeArray($arr, $key);
    }
}
