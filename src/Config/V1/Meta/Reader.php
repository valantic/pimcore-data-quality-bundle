<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Config\V1\Meta;

use Valantic\DataQualityBundle\Config\V1\AbstractReader;

class Reader extends AbstractReader implements MetaKeys
{
    /**
     * {@inheritDoc}
     */
    protected function getCurrentSectionName(): string
    {
        return self::CONFIG_SECTION_META;
    }
}
