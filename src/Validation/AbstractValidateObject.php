<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Validation;

use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Valantic\DataQualityBundle\Model\AttributeScore;
use Valantic\DataQualityBundle\Model\ObjectScore;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Repository\DataObjectConfigRepository;
use Valantic\DataQualityBundle\Service\Information\AbstractDefinitionInformation;
use Valantic\DataQualityBundle\Service\Information\DefinitionInformationFactory;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\AbstractAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\FieldCollectionAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\LocalizedAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\ObjectBrickAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\PlainAttribute;
use Valantic\DataQualityBundle\Validation\DataObject\Attributes\RelationAttribute;

abstract class AbstractValidateObject implements ValidatableInterface, ScorableInterface, ColorableInterface, PassFailInterface
{
    use ColorScoreTrait;
    protected Concrete $obj;
    protected array $groups = [];
    protected array $validationConfig;

    /**
     * Validators used for this object.
     *
     * @var AbstractAttribute[]
     */
    protected array $validators = [];
    protected AbstractDefinitionInformation $classInformation;
    protected array $skippedConstraints = [];

    /**
     * Validate an object and all its attributes.
     */
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected DefinitionInformationFactory $definitionInformationFactory,
        protected ContainerInterface $container,
        protected ConfigurationRepository $configurationRepository,
        protected FieldCollectionAttribute $fieldCollectionAttribute,
        protected LocalizedAttribute $localizedAttribute,
        protected ObjectBrickAttribute $objectBrickAttribute,
        protected PlainAttribute $plainAttribute,
        protected RelationAttribute $relationAttribute,
        protected DataObjectConfigRepository $dataObjectConfigRepository,
        protected TagAwareCacheInterface $cache,
    ) {
    }

    /**
     * Mark a constraint validator as skipped (useful to prevent recursion/cycles for relations).
     */
    public function addSkippedConstraint(string $constraintValidator): void
    {
        $this->skippedConstraints[] = $constraintValidator;
    }

    /**
     * Get the scores for the individual attributes.
     *
     * @return array<string,AttributeScore>
     */
    public function attributeScores(): array
    {
        return $this->cache->get(
            md5(sprintf('%s_%s_%s',__METHOD__, $this->obj->getId(), implode('', $this->groups))),
            function(): array {

                $attributeScores = [];
                foreach ($this->validators as $attribute => $validator) {
                    $score = new AttributeScore(value: $validator->value(), passes: $validator->passes());

                    if ($validator instanceof ScorableInterface) {
                        $score->setScore($validator->score());
                    }

                    if ($validator instanceof MultiScorableInterface) {
                        $score->setScores($validator->scores());
                    }

                    if ($validator instanceof ColorableInterface) {
                        $score->setColor($validator->color());
                    }

                    if ($validator instanceof MultiColorableInterface) {
                        $score->setColors($validator->colors());
                    }

                    $attributeScores[$attribute] = $score;
                }

        return $attributeScores;
            }
        );
    }

    /**
     * Get the scores for the whole object.
     */
    public function objectScore(): ObjectScore
    {
        return new ObjectScore(
            $this->color(),
            $this->score(),
            $this instanceof MultiScorableInterface ? $this->scores() : [],
            $this->passes(),
        );
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function passes(): bool
    {
        return $this->score() === 1.0;
    }

    /**
     * Set the object to validate.
     *
     * @param Concrete $obj the object to validate
     */
    abstract public function setObject(Concrete $obj): void;

    /**
     * Returns a list of all attributes that can be validated i.e. that exist and are configured.
     */
    protected function getValidatableAttributes(): array
    {
        return array_intersect($this->getAttributesInConfig(), $this->getAttributesInObject());
    }

    /**
     * Returns a list of configured attributes.
     */
    protected function getAttributesInConfig(): array
    {
        return array_keys($this->validationConfig);
    }

    /**
     * Returns a list of attributes present in the object.
     */
    protected function getAttributesInObject(): array
    {
        return array_keys($this->classInformation->getAllAttributes());
    }
}
