<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Enum;

use InvalidArgumentException;
use Spatie\Enum\Enum;

/**
 * @method static self green()
 * @method static self orange()
 */
class ThresholdEnum extends Enum
{
    public function defaultValue(): float
    {
        return match ($this->value) {
            'green' => 0.9,
            'orange' => 0.5,
            default => throw new InvalidArgumentException(),
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
