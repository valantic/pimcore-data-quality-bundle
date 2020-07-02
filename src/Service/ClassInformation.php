<?php

namespace Valantic\DataQualityBundle\Service;

use Pimcore\Model\DataObject\ClassDefinition;

class ClassInformation extends DefinitionInformation
{
    /**
     * {@inheritDoc}}
     */
    protected function setDefinition(): void
    {
        $this->definition= ClassDefinition::getByName($this->name);
    }
}
