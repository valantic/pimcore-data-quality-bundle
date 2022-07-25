<?php

namespace Valantic\DataQualityBundle\Shared;

use InvalidArgumentException;
use Valantic\DataQualityBundle\Enum\UtilConstants;

trait SortOrderTrait
{
    protected function sortBySortOrder(
        array $input,
        string $sortOrderField,
        string $fallbackField = 'sortOrder',
        string $sortDirection = UtilConstants::SORT_ORDER_DIR_ASC,
    ): array {
        $sortBySortOrder = [];
        $sortByFallback = [];
        $unsorted = [];

        foreach ($input as $key => $item) {
            if (array_key_exists($sortOrderField, $item) && !empty($item[$sortOrderField])) {
                $sortBySortOrder[$key] = $item;
                continue;
            }
            if (array_key_exists($fallbackField, $item) && !empty($item[$fallbackField])) {
                $sortByFallback[$key] = $item;
                continue;
            }
            $unsorted[$key] = $item;
        }

        // Sorting by $sortOrderField
        uasort(
            $sortBySortOrder,
            fn(array $a, array $b): int => $a[$sortOrderField] <=> $b[$sortOrderField]
        );

        // Sorting by $sortByFallback
        uasort(
            $sortByFallback,
            fn(array $a, array $b): int => strnatcasecmp($a[$fallbackField], $b[$fallbackField])
        );

        if (!in_array($sortDirection, UtilConstants::SORT_ORDER_DIRS, true)) {
            throw new InvalidArgumentException(sprintf('Unknown sort order %s, please use one of: %s', $sortDirection, implode(', ', UtilConstants::SORT_ORDER_DIRS)));
        }

        if ($sortDirection === UtilConstants::SORT_ORDER_DIR_DESC) {
            // Sorting in descending order is done per array partition since doing it over the whole array is usually not what is desired.
            // If that's the case, calling array_reverse() in the caller on the result would be sufficient.
            return array_filter(
                array_merge(
                    array_reverse($sortBySortOrder),
                    array_reverse($sortByFallback),
                    array_reverse($unsorted)
                )
            );
        }

        // merge the three parts together
        return array_filter(
            array_merge(
                $sortBySortOrder,
                $sortByFallback,
                $unsorted
            )
        );
    }
}
