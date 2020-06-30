<?php

namespace Valantic\DataQualityBundle\Service;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject\ClassDefinition;

class ClassInformation extends DefinitionInformation
{

    /**
     * Get the definition of the class.
     * @return ClassDefinition|null
     */
    protected function getDefinition(): ?AbstractModel
    {
        return ClassDefinition::getByName($this->name);
    }
}
