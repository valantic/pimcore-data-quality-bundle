<?php


namespace Valantic\DataQualityBundle\Validation;


use Pimcore\Model\Element\AbstractElement;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsConfig;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as MetaConfig;
use Valantic\DataQualityBundle\Service\ClassInformation;

abstract class AbstractValidateObject implements Validatable, Scorable, Colorable
{
    use ColorScoreTrait;

    /**
     * @var ConstraintsConfig
     */
    protected $constraintsConfig;

    /**
     * @var MetaConfig
     */
    protected $metaConfig;

    /**
     * @var array
     */
    protected $validationConfig;

    /**
     * Validators used for this object.
     * @var Validatable[]
     */
    protected $validators = [];

    /**
     * @var ClassInformation
     */
    protected $classInformation;

    /**
     * Validate an object and all its attributes.
     * @param ConstraintsConfig $constraintsConfig
     * @param MetaConfig $metaConfig
     */
    public function __construct(ConstraintsConfig $constraintsConfig, MetaConfig $metaConfig)
    {
        $this->constraintsConfig = $constraintsConfig;
        $this->metaConfig = $metaConfig;
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

    /**
     * Set the object to validate.
     * @param AbstractElement $obj The object to validate.
     */
    abstract public function setObject(AbstractElement $obj);

    /**
     * Get the scores for the individual attributes.
     * @return array
     */
    public function attributeScores(): array
    {
        $attributeScores = [];
        foreach ($this->validators as $attribute => $validator) {
            $attributeScores[$attribute]['color'] = null;
            $attributeScores[$attribute]['colors'] = null;
            $attributeScores[$attribute]['score'] = null;
            $attributeScores[$attribute]['scores'] = null;

            if ($validator instanceof Scorable) {
                $attributeScores[$attribute]['score'] = $validator->score();
            }

            if ($validator instanceof MultiScorable) {
                $attributeScores[$attribute]['scores'] = $validator->scores();
            }

            if ($validator instanceof Colorable) {
                $attributeScores[$attribute]['color'] = $validator->color();
            }

            if ($validator instanceof MultiColorable) {
                $attributeScores[$attribute]['colors'] = $validator->colors();
            }
        }

        return $attributeScores;
    }
}
