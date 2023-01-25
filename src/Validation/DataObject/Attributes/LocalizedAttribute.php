<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Tool;
use Throwable;
use Valantic\DataQualityBundle\Event\ConstraintFailureEvent;
use Valantic\DataQualityBundle\Validation\MultiColorableInterface;
use Valantic\DataQualityBundle\Validation\MultiScorableInterface;

class LocalizedAttribute extends AbstractAttribute implements MultiScorableInterface, MultiColorableInterface
{
    public function validate(): void
    {
        if (!$this->classInformation->isLocalizedAttribute($this->attribute)) {
            return;
        }

        try {
            foreach ($this->getValidatableLocales() as $locale) {
                // If null, value is set to empty string due to issue with incorrect null validation
                $value = $this->value()[$locale] ?: '';
                $this->violations[$locale] = $this->validator->validate($value, $this->getConstraints(), $this->groups);
            }
        } catch (\Throwable $e) {
            $this->eventDispatcher->dispatch(new ConstraintFailureEvent($e, $this->obj->getId(), $this->attribute, $this->violations));
        }
    }

    public function score(): float
    {
        if (!count($this->getConstraints()) || !count($this->getValidatableLocales())) {
            return 0;
        }

        return array_sum($this->scores()) / count($this->getValidatableLocales());
    }

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

    public function value(): array
    {
        $value = [];

        foreach ($this->getValidatableLocales() as $locale) {
            try {
                $value[$locale] = $this->valueInherited($this->obj, $locale);
            } catch (Throwable) {
                continue;
            }
        }

        return $value;
    }

    protected function getValidatableLocales(): array
    {
        return array_intersect(
            $this->dataObjectConfigRepository->get($this->obj::class)->getLocales($this->obj),
            $this->getValidLocales()
        );
    }

    /**
     * List of enabled locales.
     */
    protected function getValidLocales(): array
    {
        return Tool::getValidLanguages();
    }
}
