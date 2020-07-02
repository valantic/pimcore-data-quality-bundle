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
        if (array_key_exists($primaryLanguage, $output) && !empty($output[$primaryLanguage])) {
            return $this->shorten($output[$primaryLanguage], $threshold);
        } elseif (array_key_exists($primaryLanguage, $output) && empty($output[$primaryLanguage]) && count(array_values(array_filter($output))) > 0) {
            return $this->shorten(array_values(array_filter($output))[0], $threshold);
        }

        return $this->shorten(implode(', ', $output), $threshold);
    }
}
