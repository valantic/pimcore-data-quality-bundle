<?php

namespace Valantic\DataQualityBundle\Config\V1\Meta;

use Valantic\DataQualityBundle\Config\V1\AbstractReader;

class Reader extends AbstractReader
{
    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_META;
    }
}
