<?php

namespace Valantic\DataQualityBundle\Service\Information;

class DefinitionInformationFactory
{
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

    public function __construct(ClassInformation $classInformation, FieldCollectionInformation $fieldCollectionInformation, ObjectBrickInformation $objectBrickInformation)
    {
        $this->classInformation = $classInformation;
        $this->fieldCollectionInformation = $fieldCollectionInformation;
        $this->objectBrickInformation = $objectBrickInformation;
    }

    public function make(string $name): DefinitionInformation
    {
        $classInformation = $this->classInformation;
        $classInformation->make($this->classInformation, $this->fieldCollectionInformation, $this->objectBrickInformation, $name);

        return $classInformation;
    }
}
