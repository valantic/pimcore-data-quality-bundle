<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Information;

use Pimcore\Model\DataObject\ClassDefinition;

class ClassInformation extends AbstractDefinitionInformation
{
    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore Has to be mocked
     */
    public function getDefinition(): ?ClassDefinition
    {
        return ClassDefinition::getByName($this->name);
    }
}
