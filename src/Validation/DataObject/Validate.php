<?php

namespace Valantic\DataQualityBundle\Validation\DataObject;

use InvalidArgumentException;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\AbstractElement;
use Valantic\DataQualityBundle\Service\Information\ClassInformation;
use Valantic\DataQualityBundle\Validation\AbstractValidateObject;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\FieldCollectionAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\ObjectBrickAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\RelationAttribute;
use Valantic\DataQualityBundle\Validation\MultiScorable;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\LocalizedAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\PlainAttribute;

class Validate extends AbstractValidateObject implements MultiScorable
{
    /**
     * @var Concrete
     */
    protected $obj;

    /**
     * {@inheritDoc}
     */
    public function setObject(AbstractElement $obj): void
    {
        if (!($obj instanceof Concrete)) {
            throw new InvalidArgumentException('Please provide a Concrete DataObject.');
        }

        $this->obj = $obj;
        $this->validationConfig = $this->constraintsConfig->getForObject($obj);
        $this->classInformation = $this->definitionInformationFactory->make($this->obj->getClassName());
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        $validators = [];
        foreach ($this->getValidatableAttributes() as $attribute) {
            if ($this->classInformation->isPlainAttribute($attribute)) {
                $validator = new PlainAttribute($this->obj, $attribute, $this->constraintsConfig, $this->metaConfig, $this->eventDispatcher, $this->definitionInformationFactory, $this->container, $this->skippedConstraints);
            }
            if ($this->classInformation->isLocalizedAttribute($attribute)) {
                $validator = new LocalizedAttribute($this->obj, $attribute, $this->constraintsConfig, $this->metaConfig, $this->eventDispatcher, $this->definitionInformationFactory, $this->container, $this->skippedConstraints);
            }
            if ($this->classInformation->isObjectbrickAttribute($attribute)) {
                $validator = new ObjectBrickAttribute($this->obj, $attribute, $this->constraintsConfig, $this->metaConfig, $this->eventDispatcher, $this->definitionInformationFactory, $this->container, $this->skippedConstraints);
            }
            if ($this->classInformation->isFieldcollectionAttribute($attribute)) {
                $validator = new FieldCollectionAttribute($this->obj, $attribute, $this->constraintsConfig, $this->metaConfig, $this->eventDispatcher, $this->definitionInformationFactory, $this->container, $this->skippedConstraints);
            }
            if ($this->classInformation->isRelationAttribute($attribute)) {
                $validator = new RelationAttribute($this->obj, $attribute, $this->constraintsConfig, $this->metaConfig, $this->eventDispatcher, $this->definitionInformationFactory, $this->container, $this->skippedConstraints);
            }
            if (isset($validator)) {
                $validator->validate();
                $validators[$attribute] = $validator;
            }
            unset($validator);
        }
        $this->validators = $validators;
    }

    /**
     * {@inheritDoc}
     */
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
            if (!count($scores)) {
                $result[$multiKey] = 0;
                continue;
            }
            $result[$multiKey] = array_sum($scores) / count($scores);
        }

        return $result;
    }
}
