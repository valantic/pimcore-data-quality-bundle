<?php

namespace Valantic\DataQualityBundle\Enum;

enum UtilConstants: string
{
    // Since traits can't have constants, we use this file as a helper
    public const SORT_ORDER_DIR_ASC = 'asc';
    public const SORT_ORDER_DIR_DESC = 'desc';

    /** @return self[] */
    public static function getOrderDirs(): array
    {
        return [self::SORT_ORDER_DIR_ASC, self::SORT_ORDER_DIR_DESC];
    }
}
