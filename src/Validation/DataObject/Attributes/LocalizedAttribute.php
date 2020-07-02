<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Tool;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;
use Valantic\DataQualityBundle\Validation\MultiColorable;
use Valantic\DataQualityBundle\Validation\MultiScorable;

class LocalizedAttribute extends AbstractAttribute implements MultiScorable, MultiColorable
{
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
                $this->violations[$locale] = $this->validator->validate($this->value()[$locale], $this->getConstraints());
            }
        } catch (Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e));
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
        return $this->safeArray($this->metaConfig->getForObject($this->obj), $this->metaConfig::KEY_LOCALES);
    }

    /**
     * List of enabled locales.
     * @return array
     */
    protected function getValidLocales(): array
    {
        return Tool::getValidLanguages();
    }

    /**
     * {@inheritDoc}
     */
    public function value()
    {
        $value = [];

        foreach ($this->getValidatableLocales() as $locale) {
            $value[$locale] = $this->obj->get($this->attribute, $locale);
        }

        return $value;
    }
}
