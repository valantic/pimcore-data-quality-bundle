<?php

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Model\Element\AbstractElement;
use Pimcore\Model\ModelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Valantic\DataQualityBundle\Config\V1\Constraints\Reader as ConstraintsConfig;
use Valantic\DataQualityBundle\Config\V1\Meta\Reader as MetaConfig;
use Valantic\DataQualityBundle\Service\Information\ClassInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\AbstractAttribute;

abstract class AbstractValidateObject implements Validatable, Scorable, Colorable
{
    use ColorScoreTrait;

    /**
     * @var ModelInterface
     */
    protected $obj;

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
     * @var AbstractAttribute[]
     */
    protected $validators = [];

    /**
     * @var ClassInformation
     */
    protected $classInformation;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var DefinitionInformationFactory
     */
    protected $definitionInformationFactory;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $skippedConstraints = [];

    /**
     * Validate an object and all its attributes.
     * @param ConstraintsConfig $constraintsConfig
     * @param MetaConfig $metaConfig
     * @param EventDispatcherInterface $eventDispatcher
     * @param DefinitionInformationFactory $definitionInformationFactory
     * @param ContainerInterface $container
     */
    public function __construct(ConstraintsConfig $constraintsConfig, MetaConfig $metaConfig, EventDispatcherInterface $eventDispatcher, DefinitionInformationFactory $definitionInformationFactory, ContainerInterface $container)
    {
        $this->constraintsConfig = $constraintsConfig;
        $this->metaConfig = $metaConfig;
        $this->eventDispatcher = $eventDispatcher;
        $this->definitionInformationFactory = $definitionInformationFactory;
        $this->container = $container;
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
        return array_keys($this->classInformation->getAllAttributes());
    }

    /**
     * Set the object to validate.
     * @param AbstractElement $obj The object to validate.
     */
    abstract public function setObject(AbstractElement $obj);

    /**
     * Mark a constraint validator as skipped (useful to prevent recursion/cycles for relations).
     * @param string $constraintValidator
     */
    public function addSkippedConstraint(string $constraintValidator)
    {
        $this->skippedConstraints[] = $constraintValidator;
    }

    /**
     * Get the scores for the individual attributes.
     * @return array
     */
    public function attributeScores(): array
    {
        $attributeScores = [];
        foreach ($this->validators as $attribute => $validator) {
            $attributeScores[$attribute]['color'] = null;
            $attributeScores[$attribute]['colors'] = [];
            $attributeScores[$attribute]['score'] = null;
            $attributeScores[$attribute]['scores'] = [];
            $attributeScores[$attribute]['value'] = $validator->value();

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
