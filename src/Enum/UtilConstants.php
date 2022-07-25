<?php

namespace Valantic\DataQualityBundle\Enum;

use Spatie\Enum\Enum;

// TODO: refactor enum once PHP 8.1+
interface UtilConstants
{
    // Since traits can't have constants, we use this file as a helper
    public const SORT_ORDER_DIR_ASC = 'asc';
    public const SORT_ORDER_DIR_DESC = 'desc';
    public const SORT_ORDER_DIRS = [self::SORT_ORDER_DIR_ASC, self::SORT_ORDER_DIR_DESC];
}
