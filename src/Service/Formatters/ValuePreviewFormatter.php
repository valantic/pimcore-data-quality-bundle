<?php

namespace Valantic\DataQualityBundle\Service\Formatters;

use Pimcore\Tool;

class ValuePreviewFormatter extends ValueFormatter
{
    /**
     * {@inheritDoc}
     */
    public function format($input)
    {
        $output = parent::format($input);
        $threshold = 50;

        if (!is_array($output)) {
            return $this->shorten($output, $threshold);
        }

        $primaryLanguage = Tool::getValidLanguages()[0];
        if (array_key_exists($primaryLanguage, $output)) {
            return $this->shorten($output[$primaryLanguage], $threshold);
        }

        return $this->shorten(implode(', ', $output), $threshold);
    }
}
