<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject;

use Pimcore\Cache;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Model\AttributeScore;
use Valantic\DataQualityBundle\Service\CacheService;
use Valantic\DataQualityBundle\Validation\AbstractValidateObject;
use Valantic\DataQualityBundle\Validation\MultiColorableInterface;
use Valantic\DataQualityBundle\Validation\MultiScorableInterface;

class Validate extends AbstractValidateObject implements MultiColorableInterface, MultiScorableInterface
{
    public function setObject(Concrete $obj): void
    {
        $this->obj = $obj;
        $this->validationConfig = $this->configurationRepository->getAttributesForClass($obj::class);
        $this->classInformation = $this->configurationRepository->getClassInformation($this->obj::class);
    }

    public function validate(): void
    {
        $validators = [];

        $orgHideUnpublished = DataObject::doHideUnpublished();
        $orgGetInheritedValues = DataObject::getGetInheritedValues();
        $orgGetFallbackValues = DataObject\Localizedfield::getGetFallbackValues();
        $orgLocale = $this->localeService->getLocale();

        DataObject::setHideUnpublished(true);
        DataObject::setGetInheritedValues(true);
        DataObject\Localizedfield::setGetFallbackValues(!$this->getIgnoreFallbackLanguage());

        $objectData = [];
        foreach ($this->getValidatableAttributes() as $attribute) {
            foreach ($this->getValidatableLocales() as $locale) {
                // Force set translator to the current locale
                $this->localeService->setLocale($locale);

                $parts = explode('.', (string) $attribute, 3);
                $getter = 'get' . ucfirst($parts[0]);
                if (method_exists($this->obj, $getter)) {
                    $objectData[$attribute][$locale] = $this->normalizer->normalize($this->obj->get($parts[0]), null, [
                        'resource_attribute' => $attribute,
                        'resource_object' => $this->obj,
                    ]);
                }
            }
        }

        $this->localeService->setLocale($orgLocale);
        DataObject::setHideUnpublished($orgHideUnpublished);
        DataObject::setGetInheritedValues($orgGetInheritedValues);
        DataObject\Localizedfield::setGetFallbackValues($orgGetFallbackValues);

        /**
         * @var string $attribute
         * @var array $values
         */
        foreach ($objectData as $attribute => $values) {
            $arguments = [
                $this->obj,
                $attribute,
                $values,
                $this->getGroups(),
                $this->skippedConstraints,
                $this->configurationRepository->getRulesForAttribute($this->obj::class, $attribute),
            ];

            if ($this->classInformation->isLocalizedAttribute($attribute)) {
                $validator = clone $this->localizedAttribute;
            } else {
                $validator = clone $this->simpleAttribute;
            }

            $validator->configure(...$arguments);
            $validator->validate();
            $validators[$attribute] = $validator;
        }

        $this->validators = $validators;
    }

    public function score(): float
    {
        if (count($this->getValidatableAttributes()) === 0) {
            return 0;
        }

        $score = array_sum($this->scores()) / count($this->scores());

        $fieldName = $this->configurationRepository->getScoreFieldName($this->obj::class);

        if (!empty($fieldName) && property_exists($this->obj, $fieldName)) {
            $currentScore = $this->dataObjectRepository->getValue($this->obj, $fieldName);
            $newScore = $this->percentageFormatter->format($score);

            if ($currentScore !== $newScore) {
                $this->dataObjectRepository->update($this->obj, [
                    $fieldName => $newScore,
                ]);
            }
        }

        return $score;
    }

    public function scores(): array
    {
        $cacheKey = CacheService::getScoreCacheKey((int) $this->obj->getId());
        $cacheTags = CacheService::getTags((int) $this->obj->getId(), $this->obj::class);

        $result = Cache::load($cacheKey);
        if (!$this->cacheScores || $result === false) {
            $scores = array_map(
                fn (AttributeScore $attributeScore) => $attributeScore->getScores(),
                $this->calculateScores(),
            );

            // get (array_column) all attribute scores that have (array_filter) multiple scores
            $multiScores = array_values(array_filter($scores));

            if (count($multiScores) === 0) {
                return [];
            }

            $result = [];
            // iterate over the keys of all multiscores (... requires the array_values above)
            foreach (array_keys(array_merge_recursive(...$multiScores)) as $multiKey) {
                $scores = array_column($multiScores, $multiKey);
                if (count($scores) === 0) {
                    $result[$multiKey] = 0;
                    continue;
                }
                $result[$multiKey] = array_sum($scores) / count($scores);
            }

            Cache::save($result, $cacheKey, $cacheTags);
        }

        return $result;
    }

    protected function getValidatableLocales(): array
    {
        $locales = $this->dataObjectConfigRepository->get($this->obj::class)->getLocales($this->obj);

        if (empty($locales)) {
            return $this->getValidLocales();
        }

        return array_intersect($locales, $this->getValidLocales());
    }

    /**
     * List of enabled locales.
     */
    protected function getValidLocales(): array
    {
        return $this->allowedLocales;
    }
}
