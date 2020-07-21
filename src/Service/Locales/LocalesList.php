<?php

namespace Valantic\DataQualityBundle\Service\Locales;

use Pimcore\Tool;

class LocalesList
{
    /**
     * Returns a list of locales that can be used.
     * @return array
     */
    public function all(): array
    {
        return Tool::getValidLanguages();
    }
}
