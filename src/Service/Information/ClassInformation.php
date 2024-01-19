<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Information;

use Pimcore\Model\DataObject\ClassDefinition;
use Valantic\DataQualityBundle\Shared\ClassBasenameTrait;

class ClassInformation extends AbstractDefinitionInformation
{
    use ClassBasenameTrait;

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore Has to be mocked
     */
    public function getDefinition(): ?ClassDefinition
    {
        return ClassDefinition::getByName(self::classBasename($this->name));
    }
}
