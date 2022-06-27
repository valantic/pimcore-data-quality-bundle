<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Enum;

enum ThresholdEnum: string
{
    public function defaultValue(): float
    {
        return match ($this) {
            self::THRESHOLD_GREEN => 0.9,
            self::THRESHOLD_ORANGE => 0.5,
        };
    }
    case THRESHOLD_GREEN = 'green';
    case THRESHOLD_ORANGE = 'orange';
}
