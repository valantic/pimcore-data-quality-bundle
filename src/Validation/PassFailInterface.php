<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

interface PassFailInterface
{
    public function passes(): bool;
}
