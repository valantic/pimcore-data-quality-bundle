<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1\Meta;

interface MetaKeys
{
    public const KEY_LOCALES = 'locales';
    public const KEY_THRESHOLD_GREEN = 'threshold_green';
    public const KEY_THRESHOLD_ORANGE = 'threshold_orange';
    public const KEY_NESTING_LIMIT = 'nesting_limit';
}
