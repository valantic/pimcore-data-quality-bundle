<?php

namespace Valantic\DataQualityBundle\Service\Information;

use InvalidArgumentException;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\Fieldcollection\Definition as FieldcollectionDefinition;
use Pimcore\Model\DataObject\Objectbrick\Definition as ObjectbrickDefinition;

abstract class DefinitionInformation
{
    public const TYPE_PLAIN = 'plain';
    public const TYPE_LOCALIZED = 'localized';
    public const TYPE_OBJECTBRICK = 'objectbrick';
    public const TYPE_FIELDCOLLECTION = 'fieldcollection';
    public const TYPE_CLASSIFICATIONSTORE = 'classificationstore';
    public const TYPE_RELATION = 'relation';

    /**
     * The class' base name
     * @var string
     */
    protected $name;

    /**
     * @var ClassDefinition|FieldcollectionDefinition|ObjectbrickDefinition
     */
    protected $definition;

    /**
     * @var array Holds all localized attributes.
     */
    protected $localizedAttributes = [];

    /**
     * @var array Holds all objectbrick attributes.
     */
    protected $objectbrickAttributes = [];

    /**
     * @var array Holds all fieldcollection attributes.
     */
    protected $fieldcollectionAttributes = [];

    /**
     * @var array Holds all classificationstore attributes.
     */
    protected $classificationstoreAttributes = [];

    /**
     * @var array Holds all relation attributes.
     */
    protected $relationAttributes = [];

    /**
     * @var array Holds all plain attributes.
     */
    protected $plainAttributes = [];

    /**
     * @var ObjectBrickInformation[] Holds all objectbrick class information objects.
     */
    protected $objectbrickInformationInstances = [];

    /**
     * @var FieldCollectionInformation[] Holds all fieldcollection class information objects.
     */
    protected $fieldcollectionInformationInstances = [];

    /**
     * @var ClassInformation
     */
    protected $classInformation;

    /**
     * @var FieldCollectionInformation
     */
    protected $fieldCollectionInformation;

    /**
     * @var ObjectBrickInformation
     */
    protected $objectBrickInformation;

    /**
     * @param ClassInformation $classInformation
     * @param FieldCollectionInformation $fieldCollectionInformation
     * @param ObjectBrickInformation $objectBrickInformation
     * @param string $name
     *
     * @return void
     */
    public function make(ClassInformation $classInformation, FieldCollectionInformation $fieldCollectionInformation, ObjectBrickInformation $objectBrickInformation, string $name): void
    {
        $this->classInformation = $classInformation;
        $this->fieldCollectionInformation = $fieldCollectionInformation;
        $this->objectBrickInformation = $objectBrickInformation;

        $this->setName($name);
    }

    /**
     * Set the name and pre-load data.
     *
     * @param string $name
     *
     * @return void
     */
    protected function setName(string $name): void
    {
        if (strpos($name, '\\') !== false) {
            $nameParts = explode('\\', $name);
            $name = $nameParts[count($nameParts) - 1];
        }
        $this->name = $name;

        $definition = $this->getDefinition();
        if (!$definition) {
            throw new InvalidArgumentException('Failed to load definition');
        }
        $this->definition = $definition;

        $this->findAllAttributes();
    }

    /**
     * Gets the canonical class name
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns an array of all attributes present in this class keyed by their names.
     * @return array
     */
    public function getAllAttributes(): array
    {
        return array_merge_recursive(
            $this->objectbrickAttributes,
            $this->fieldcollectionAttributes,
            $this->classificationstoreAttributes,
            $this->relationAttributes,
            $this->localizedAttributes,
            $this->plainAttributes
        );
    }

    /**
     * Get the type of a class attribute.
     *
     * @param string $attribute
     *
     * @return string|null
     */
    public function getAttributeType(string $attribute): ?string
    {
        if ($this->isPlainAttribute($attribute)) {
            return self::TYPE_PLAIN;
        }
        if ($this->isLocalizedAttribute($attribute)) {
            return self::TYPE_LOCALIZED;
        }
        if ($this->isObjectbrickAttribute($attribute)) {
            return self::TYPE_OBJECTBRICK;
        }
        if ($this->isFieldcollectionAttribute($attribute)) {
            return self::TYPE_FIELDCOLLECTION;
        }
        if ($this->isClassificationstoreAttribute($attribute)) {
            return self::TYPE_CLASSIFICATIONSTORE;
        }
        if ($this->isRelationAttribute($attribute)) {
            return self::TYPE_RELATION;
        }

        return null;
    }

    /**
     * Checks whether $attribute is an attribute.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function isAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->getAllAttributes());
    }

    /**
     * Checks whether $attribute is plain.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function isPlainAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->plainAttributes);
    }

    /**
     * Checks whether $attribute is localized.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function isLocalizedAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->localizedAttributes);
    }

    /**
     * Checks whether $attribute is an objectbrick.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function isObjectbrickAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->objectbrickAttributes);
    }

    /**
     * Checks whether $attribute is a fieldcollection.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function isFieldcollectionAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->fieldcollectionAttributes);
    }

    /**
     * Checks whether $attribute is a classificationstore.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function isClassificationstoreAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->classificationstoreAttributes);
    }

    /**
     * Checks whether $attribute is a relation.
     *
     * @param string $attribute
     *
     * @return bool
     */
    public function isRelationAttribute(string $attribute): bool
    {
        return array_key_exists($attribute, $this->relationAttributes);
    }

    /**
     * Get the definition of the class.
     * @return null|ClassDefinition|FieldcollectionDefinition|ObjectbrickDefinition
     */
    abstract public function getDefinition();

    /**
     * Finds all attributes.
     *
     * @return void
     */
    protected function findAllAttributes(): void
    {
        $this->findLocalizedAttributes();
        $this->findObjectbrickAttributes();
        $this->findFieldcollectionAttributes();
        $this->findClassificationstoreAttributes();
        $this->findRelationAttributes();
        $this->findPlainAttributes();
    }

    /**
     * Finds all localized attributes present in this class keyed by their names
     * and saves them in the corresponding property..
     * @return void
     */
    protected function findLocalizedAttributes(): void
    {
        $fieldDefinitions = [];
        foreach ($this->definition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Localizedfields) {
                foreach ($fieldDefinition->getChildren() as $child) {
                    $fieldDefinitions[$child->getName()] = $child;
                }
            }
        }

        $this->localizedAttributes = $fieldDefinitions;
    }

    /**
     * Finds all objectbrick attributes present in this class keyed by their names
     * and saves them in the corresponding property..
     * @return void
     */
    protected function findObjectbrickAttributes(): void
    {
        $fieldDefinitions = [];
        foreach ($this->definition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Objectbricks) {
                foreach ($fieldDefinition->getAllowedTypes() as $type) {
                    $this->objectBrickInformation->setName($type);
                    $information = clone $this->objectBrickInformation;
                    $this->objectbrickInformationInstances[$fieldDefinition->getName() . '.' . $type] = $information;
                    $attributes = $information->getAllAttributes();
                    foreach ($attributes as $name => $attribute) {
                        $fieldDefinitions[$fieldDefinition->getName() . '.' . $type . '.' . $name] = $attribute;
                    }
                }
            }
        }


        $this->objectbrickAttributes = $fieldDefinitions;
    }

    /**
     * Finds all fieldcollection attributes present in this class keyed by their names
     * and saves them in the corresponding property..
     * @return void
     */
    protected function findFieldcollectionAttributes(): void
    {
        $fieldDefinitions = [];
        foreach ($this->definition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Fieldcollections) {
                foreach ($fieldDefinition->getAllowedTypes() as $type) {
                    $this->fieldCollectionInformation->setName($type);
                    $information = clone $this->fieldCollectionInformation;
                    $this->fieldcollectionInformationInstances[$fieldDefinition->getName() . '.' . $type] = $information;
                    $attributes = $information->getAllAttributes();
                    foreach ($attributes as $name => $attribute) {
                        $fieldDefinitions[$fieldDefinition->getName() . '.' . $type . '.' . $name] = $attribute;
                    }
                }
            }
        }

        $this->fieldcollectionAttributes = $fieldDefinitions;
    }

    /**
     * Finds all classificationstore attributes present in this class keyed by their names
     * and saves them in the corresponding property..
     * @return void
     */
    protected function findClassificationstoreAttributes(): void
    {
        $fieldDefinitions = [];
        foreach ($this->definition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Classificationstore) {
                // TODO: finish implementation
                $fieldDefinitions[$fieldDefinition->getName()] = $fieldDefinition;
            }
        }

        $this->classificationstoreAttributes = $fieldDefinitions;
    }


    /**
     * Finds all relation attributes present in this class keyed by their names
     * and saves them in the corresponding property..
     * @return void
     */
    protected function findRelationAttributes(): void
    {
        $fieldDefinitions = [];
        foreach ($this->definition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof AbstractRelations) {
                $fieldDefinitions[$fieldDefinition->getName()] = $fieldDefinition;
            }
        }

        $this->relationAttributes = $fieldDefinitions;
    }

    /**
     * Finds all plain attributes present in this class keyed by their names
     * and saves them in the corresponding property..
     * @return void
     */
    protected function findPlainAttributes(): void
    {
        $fieldDefinitions = [];
        foreach ($this->definition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Localizedfields || $fieldDefinition instanceof Fieldcollections || $fieldDefinition instanceof Objectbricks || $fieldDefinition instanceof Classificationstore || $fieldDefinition instanceof AbstractRelations) {
                continue;
            }
            $fieldDefinitions[$fieldDefinition->getName()] = $fieldDefinition;
        }

        $this->plainAttributes = $fieldDefinitions;
    }

    /**
     * If available, return the label for this attribute.
     *
     * @param string $attribute
     *
     * @return string
     */
    public function getAttributeLabel(string $attribute): string
    {
        if (!$this->isAttribute($attribute)) {
            return '';
        }
        if ($this->isLocalizedAttribute($attribute) || $this->isPlainAttribute($attribute) || $this->isRelationAttribute($attribute)) {
            return sprintf('%s', $this->getAllAttributes()[$attribute]->getTitle());
        }
        if ($this->isObjectbrickAttribute($attribute)) {
            $parts = explode('.', $attribute);

            return sprintf(
                '%s > %s > %s',
                $this->definition->getFieldDefinition($parts[0])->getTitle(),
                $this->objectbrickInformationInstances[$parts[0] . '.' . $parts[1]]->definition->getTitle(),
                $this->objectbrickAttributes[$attribute]->getTitle()
            );
        }
        if ($this->isFieldcollectionAttribute($attribute)) {
            $parts = explode('.', $attribute);

            return sprintf(
                '%s > %s > %s',
                $this->definition->getFieldDefinition($parts[0])->getTitle(),
                $this->fieldcollectionInformationInstances[$parts[0] . '.' . $parts[1]]->definition->getTitle(),
                $this->fieldcollectionAttributes[$attribute]->getTitle()
            );
        }

        return $attribute;
    }

}
