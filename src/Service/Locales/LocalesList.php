<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Locales;

use Pimcore\Tool;

class LocalesList
{
    /**
     * Returns a list of locales that can be used.
     */
    public function all(): array
    {
        return Tool::getValidLanguages();
    }
}
