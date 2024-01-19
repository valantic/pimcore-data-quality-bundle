<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Enum;

enum ThresholdEnum: int
{
    case green = 90;
    case orange = 50;
}
