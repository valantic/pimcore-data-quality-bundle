<?php

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsConfig;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as MetaConfig;
use Valantic\DataQualityBundle\Service\ClassInformation;

abstract class AbstractValidateAttribute implements Validatable, Scorable, Colorable
{
    use ColorScoreTrait;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var Concrete
     */
    protected $obj;

    /**
     * @var string
     */
    protected $attribute;

    /**
     * @var ConstraintsConfig
     */
    protected $constraintsConfig;

    /**
     * @var array
     */
    protected $validationConfig;

    /**
     * Violations found during validation.
     * @var ConstraintViolationList[]
     */
    protected $violations = [];

    /**
     * @var ClassInformation
     */
    protected $classInformation;

    /**
     * @var MetaConfig
     */
    protected $metaConfig;

    /**
     * Validates an attribute of an object.
     *
     * @param Concrete $obj Object to validate
     * @param string $attribute Attribute to validate
     * @param ConstraintsConfig $constraintsConfig
     * @param MetaConfig $metaConfig
     */
    public function __construct(Concrete $obj, string $attribute, ConstraintsConfig $constraintsConfig, MetaConfig $metaConfig)
    {
        $validationBuilder = Validation::createValidatorBuilder();
        $this->validator = $validationBuilder->getValidator();
        $this->obj = $obj;
        $this->attribute = $attribute;
        $this->constraintsConfig = $constraintsConfig;
        $this->validationConfig = $constraintsConfig->getForObjectAttribute($obj, $attribute);
        $this->classInformation = new ClassInformation($this->obj->getClassName());
        $this->metaConfig = $metaConfig;
    }

    /**
     * {@inheritDoc}
     */
    public function score(): float
    {
        if (!count($this->getConstraints())) {
            return 0;
        }

        return 1 - ($this->violations->count() / count($this->getConstraints()));
    }

    /**
     * Get the validation rules for this attribute.
     * @return array
     */
    protected function getRules(): array
    {
        return $this->validationConfig;
    }

    /**
     * Get instantiated constraint classes. Invalid constrains are discarded.
     * @return array
     */
    protected function getConstraints(): array
    {
        $constraints = [];
        foreach ($this->getRules() as $name => $params) {
            if (strpos($name, '\\') === false) {
                $name = 'Symfony\Component\Validator\Constraints\\' . $name;
            }

            if (!class_exists($name)) {
                continue;
            }

            try {
                $constraints[] = new $name(...([$params]));
            } catch (Throwable $throwable) {
                // TODO: emit event
            }
        }

        return $constraints;
    }

    /**
     * Returns the value being validated.
     * @return mixed
     */
    abstract public function value();
}
