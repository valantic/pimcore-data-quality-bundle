<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

interface Validatable
{
    /**
     * Run validation based on its configuration.
     */
    public function validate(): void;
}
