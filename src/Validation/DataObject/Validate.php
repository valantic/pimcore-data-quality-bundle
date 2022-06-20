<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation\DataObject;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Validation\AbstractValidateObject;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\FieldCollectionAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\LocalizedAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\ObjectBrickAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\PlainAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\RelationAttribute;
use Valantic\DataQualityBundle\Validation\MultiScorableInterface;

class Validate extends AbstractValidateObject implements MultiScorableInterface
{
    protected Concrete $obj;

    public function setObject(Concrete $obj): void
    {
        $this->obj = $obj;
        $this->validationConfig = $this->configurationRepository->getForClass($obj::class);
        $this->classInformation = $this->definitionInformationFactory->make($this->obj::class);
    }

    public function validate(): void
    {
        $validators = [];
        foreach ($this->getValidatableAttributes() as $attribute) {
            if ($this->classInformation->isPlainAttribute($attribute)) {
                $validator = new PlainAttribute(
                    $this->obj,
                    $attribute,
                    $this->eventDispatcher,
                    $this->definitionInformationFactory,
                    $this->container,
                    $this->skippedConstraints,
                    $this->configurationRepository,
                );
            }
            if ($this->classInformation->isLocalizedAttribute($attribute)) {
                $validator = new LocalizedAttribute(
                    $this->obj,
                    $attribute,
                    $this->eventDispatcher,
                    $this->definitionInformationFactory,
                    $this->container,
                    $this->skippedConstraints,
                    $this->configurationRepository,
                );
            }
            if ($this->classInformation->isObjectbrickAttribute($attribute)) {
                $validator = new ObjectBrickAttribute(
                    $this->obj,
                    $attribute,
                    $this->eventDispatcher,
                    $this->definitionInformationFactory,
                    $this->container,
                    $this->skippedConstraints,
                    $this->configurationRepository,
                );
            }
            if ($this->classInformation->isFieldcollectionAttribute($attribute)) {
                $validator = new FieldCollectionAttribute(
                    $this->obj,
                    $attribute,
                    $this->eventDispatcher,
                    $this->definitionInformationFactory,
                    $this->container,
                    $this->skippedConstraints,
                    $this->configurationRepository,
                );
            }
            if ($this->classInformation->isRelationAttribute($attribute)) {
                $validator = new RelationAttribute(
                    $this->obj,
                    $attribute,
                    $this->eventDispatcher,
                    $this->definitionInformationFactory,
                    $this->container,
                    $this->skippedConstraints,
                    $this->configurationRepository,
                );
            }
            if (isset($validator)) {
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
