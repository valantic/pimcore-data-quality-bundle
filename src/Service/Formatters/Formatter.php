<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Formatters;

interface Formatter
{
    /**
     * Formats $input and returns the formatted value.
     */
    public function format(mixed $input): mixed;
}
