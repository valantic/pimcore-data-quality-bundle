<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Information;

class DefinitionInformationFactory
{
    public function __construct(
        protected ClassInformation $classInformation,
        protected FieldCollectionInformation $fieldCollectionInformation,
        protected ObjectBrickInformation $objectBrickInformation,
    ) {
    }

    /**
     * @param class-string $name
     */
    public function make(string $name): AbstractDefinitionInformation
    {
        $classInformation = $this->classInformation;
        $classInformation->make($this->classInformation, $this->fieldCollectionInformation, $this->objectBrickInformation, $name);

        return $classInformation;
    }
}
