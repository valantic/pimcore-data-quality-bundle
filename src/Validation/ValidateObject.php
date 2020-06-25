<?php

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsConfig;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as MetaConfig;
use Valantic\DataQualityBundle\Service\ClassInformation;

class ValidateObject implements Validatable, Scorable, MultiScorable
{
    /**
     * @var Concrete
     */
    protected $obj;

    /**
     * @var array
     */
    protected $validationConfig;

    /**
     * @var ConstraintsConfig
     */
    protected $constraintsConfig;

    /**
     * Validators used for this object.
     * @var ValidatePlainAttribute[]
     */
    protected $validators = [];

    /**
     * @var ClassInformation
     */
    protected $classInformation;

    /**
     * @var MetaConfig
     */
    protected $metaConfig;

    /**
     * Validate an object and all its attributes.
     * @param Concrete $obj The object to validate.
     * @param ConstraintsConfig $constraintsConfig
     * @param MetaConfig $metaConfig
     */
    public function __construct(Concrete $obj, ConstraintsConfig $constraintsConfig, MetaConfig $metaConfig)
    {
        $this->obj = $obj;
        $this->validationConfig = $constraintsConfig->getForObject($obj);
        $this->constraintsConfig = $constraintsConfig;
        $this->classInformation = new ClassInformation($this->obj->getClassName());
        $this->metaConfig = $metaConfig;
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

    /**
     * Returns a list of all attributes that can be validated i.e. that exist and are configured.
     * @return array
     */
    protected function getValidatableAttributes(): array
    {
        return array_intersect($this->getAttributesInConfig(), $this->getAttributesInObject());
    }

    /**
     * Returns a list of configured attributes.
     * @return array
     */
    protected function getAttributesInConfig(): array
    {
        return array_keys($this->validationConfig);
    }

    /**
     * Returns a list of attributes present in the object.
     * @return array
     */
    protected function getAttributesInObject(): array
    {
        return array_keys($this->classInformation->getAttributesFlattened());
    }
}
