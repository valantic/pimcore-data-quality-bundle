<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Formatters;

class PercentageFormatter implements FormatterInterface
{
    public function format(mixed $input): mixed
    {
        return (int) round($input * 100);
    }
}
