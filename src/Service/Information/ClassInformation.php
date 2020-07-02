<?php

namespace Valantic\DataQualityBundle\Service\Information;

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
