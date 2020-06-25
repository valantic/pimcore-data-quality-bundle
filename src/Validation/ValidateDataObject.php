<?php

namespace Valantic\DataQualityBundle\Validation;

use InvalidArgumentException;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\AbstractElement;
use Valantic\DataQualityBundle\Service\ClassInformation;

class ValidateDataObject extends AbstractValidateObject implements MultiScorable
{
    /**
     * @var Concrete
     */
    protected $obj;

    /**
     * {@inheritDoc}
     */
    public function setObject(AbstractElement $obj)
    {
        if(!($obj instanceof Concrete)){
            throw new InvalidArgumentException('Please provide a Concrete DataObject.');
        }

        $this->obj = $obj;
        $this->validationConfig = $this->constraintsConfig->getForObject($obj);
        $this->classInformation = new ClassInformation($this->obj->getClassName());
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        $validators = [];
        foreach ($this->getValidatableAttributes() as $attribute) {
            if ($this->classInformation->isPlainAttribute($attribute)) {
                $validator = new ValidatePlainAttribute($this->obj, $attribute, $this->constraintsConfig, $this->metaConfig);
            }
            if ($this->classInformation->isLocalizedAttribute($attribute)) {
                $validator = new ValidateLocalizedAttribute($this->obj, $attribute, $this->constraintsConfig, $this->metaConfig);
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

    /**
     * Get the scores for the individual attributes.
     * @return array
     */
    public function attributeScores(): array
    {
        $attributeScores = [];
        foreach ($this->validators as $attribute => $validator) {
            $attributeScores[$attribute]['score'] = null;
            $attributeScores[$attribute]['scores'] = null;

            if ($validator instanceof Scorable) {
                $attributeScores[$attribute]['score'] = $validator->score();
            }

            if ($validator instanceof MultiScorable) {
                $attributeScores[$attribute]['scores'] = $validator->scores();
            }
        }

        return $attributeScores;
    }
}
