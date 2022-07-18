<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Enum;

use Spatie\Enum\Enum;

/**
 * @method static self green()
 * @method static self orange()
 * @method static self archived()
 */
class ThresholdEnum extends Enum
{
    public function defaultValue(): float
    {
        return match ($this) {
            self::THRESHOLD_GREEN => 0.9,
            self::THRESHOLD_ORANGE => 0.5,
        };
    }

    protected static function values(): array
    {
        return [
            'green' => 'green',
            'orange' => 'orange',
        ];
    }
}
