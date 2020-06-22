<?php

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Model\DataObject\Concrete;
use Valantic\DataQualityBundle\Config\V1\Reader as ConfigReader;

class ValidateObject implements Validatable, Scorable
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
     * @var ConfigReader
     */
    protected $config;

    /**
     * Validators used for this object.
     * @var ValidateAttribute[]
     */
    protected $validators = [];

    /**
     * Validate an object and all its attributes.
     * @param Concrete $obj The object to validate.
     * @param ConfigReader $config
     */
    public function __construct(Concrete $obj, ConfigReader $config)
    {
        $this->obj = $obj;
        $this->validationConfig = $config->getForObject($obj);
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function validate()
    {
        $validators = [];
        foreach ($this->getValidatableAttributes() as $attribute) {
            $validator = new ValidateAttribute($this->obj, $attribute, $this->config);
            $validator->validate();
            $validators[$attribute] = $validator;
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

        return array_sum($this->attributeScores()) / count($this->getValidatableAttributes());
    }

    /**
     * Get the scores for the individual attributes.
     * @return array
     */
    public function attributeScores(): array
    {
        $attributeScores = [];
        foreach ($this->validators as $attribute => $validator) {
            $attributeScores[$attribute] = $validator->score();
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
        return array_keys($this->obj->getClass()->getFieldDefinitions());
    }
}
