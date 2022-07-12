<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Validation\AbstractValidateObject;
use Valantic\DataQualityBundle\Validation\MultiScorableInterface;

class Validate extends AbstractValidateObject implements MultiScorableInterface
{
    public function setObject(Concrete $obj): void
    {
        $this->obj = $obj;
        $this->validationConfig = $this->configurationRepository->getAttributesForClass($obj::class);
        $this->classInformation = $this->definitionInformationFactory->make($this->obj::class);
    }

    public function validate(): void
    {
        $validators = [];
        foreach ($this->getValidatableAttributes() as $attribute) {
            $arguments = [
                $this->obj,
                $attribute,
                $this->groups,
                $this->skippedConstraints,
            ];

            if ($this->classInformation->isPlainAttribute($attribute)) {
                $validator = clone $this->plainAttribute;
            } elseif ($this->classInformation->isLocalizedAttribute($attribute)) {
                $validator = clone $this->localizedAttribute;
            } elseif ($this->classInformation->isObjectbrickAttribute($attribute)) {
                $validator = clone $this->objectBrickAttribute;
            } elseif ($this->classInformation->isFieldcollectionAttribute($attribute)) {
                $validator = clone $this->fieldCollectionAttribute;
            } elseif ($this->classInformation->isRelationAttribute($attribute)) {
                $validator = clone $this->relationAttribute;
            }

            if (isset($validator)) {
                $validator->configure(...$arguments);
                $validator->validate();
                $validators[$attribute] = $validator;
            }
            unset($validator);
        }
        $this->validators = $validators;
    }

    public function score(): float
    {
        if (!count($this->getValidatableAttributes())) {
            return 0;
        }

        return array_sum(array_column($this->attributeScores(), 'score')) / count($this->getValidatableAttributes());
    }

    public function scores(): array
    {
        // get (array_column) all attribute scores that have (array_filter) multiple scores
        $multiScores = array_values(array_filter(array_column($this->attributeScores(), 'scores')));

        if (!count($multiScores)) {
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

        return $result;
    }
}
