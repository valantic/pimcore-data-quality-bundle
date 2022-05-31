<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Throwable;

class PlainAttribute extends AbstractAttribute
{
    public function value(): mixed
    {
        try {
            return $this->valueInherited($this->obj, null);
        } catch (Throwable) {
            return null;
        }
    }
}
