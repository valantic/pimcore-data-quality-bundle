<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Formatters;

use Valantic\DataQualityBundle\Service\Locales\LocalesList;

class ValuePreviewFormatter extends ValueFormatter
{
    /**
     * ValuePreviewFormatter constructor.
     *
     * @param LocalesList $localesList
     */
    public function __construct(protected LocalesList $localesList)
    {
    }

    public function format(mixed $input): mixed
    {
        $output = parent::format($input);
        $threshold = 50;

        if (!is_array($output)) {
            return $this->shorten($output, $threshold);
        }

        $primaryLanguage = $this->localesList->all()[0];
        if (array_key_exists($primaryLanguage, $output) && !empty($output[$primaryLanguage])) {
            return $this->shorten($output[$primaryLanguage], $threshold);
        } elseif (array_key_exists($primaryLanguage, $output) && empty($output[$primaryLanguage]) && count(array_filter($output)) > 0) {
            return $this->shorten(array_values(array_filter($output))[0], $threshold);
        }

        return $this->shorten(implode(', ', array_filter($output)), $threshold);
    }
}
