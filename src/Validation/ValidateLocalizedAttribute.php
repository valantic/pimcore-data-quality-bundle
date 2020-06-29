<?php

namespace Valantic\DataQualityBundle\Validation;

use Exception;
use Pimcore\Tool;
use Symfony\Component\Validator\ConstraintViolationList;
use Throwable;

class ValidateLocalizedAttribute extends AbstractValidateAttribute implements MultiScorable, MultiColorable
{
    /**
     * Violations found during validation.
     * @var []
     */
    protected $violations = [];

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        if (!$this->classInformation->isLocalizedAttribute($this->attribute)) {
            return;
        }

        try {
            foreach ($this->getValidatableLocales() as $locale) {
                $this->violations[$locale] = $this->validator->validate($this->obj->get($this->attribute, $locale), $this->getConstraints());
            }
        } catch (Exception $e) {
            // TODO: emit event
        }
    }

    /**
     * {@inheritDoc}
     */
    public function score(): float
    {
        if (!count($this->getConstraints()) || !count($this->getValidatableLocales())) {
            return 0;
        }

        return array_sum($this->scores()) / count($this->getValidatableLocales());
    }

    /**
     * {@inheritDoc}
     */
    public function scores(): array
    {
        if (!count($this->getConstraints())) {
            return [];
        }

        $scores = [];

        foreach ($this->getValidatableLocales() as $locale) {
            $scores[$locale] = 1 - (count($this->violations[$locale]) / count($this->getConstraints()));
        }

        return $scores;
    }

    /**
     * {@inheritDoc}
     */
    public function colors(): array
    {
        if (!count($this->getConstraints())) {
            return [];
        }

        $scores = $this->scores();
        $colors = [];

        foreach ($this->getValidatableLocales() as $locale) {
            $colors[$locale] = $this->calculateColor($scores[$locale]);
        }

        return $colors;
    }

    protected function getValidatableLocales(): array
    {
        return array_intersect($this->getLocalesInConfig(), $this->getValidLocales());
    }

    /**
     * Returns a list of configured attributes.
     * @return array
     */
    protected function getLocalesInConfig(): array
    {
        return $this->metaConfig->getForObject($this->obj)[$this->metaConfig::KEY_LOCALES];
    }

    protected function getValidLocales(): array
    {
        return Tool::getValidLanguages();
    }
}
