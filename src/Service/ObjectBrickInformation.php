<?php

namespace Valantic\DataQualityBundle\Service;

use Pimcore\Model\DataObject\Objectbrick\Definition\Listing;

class ObjectBrickInformation extends DefinitionInformation
{
    /**
     * {@inheritDoc}}
     */
    protected function setDefinition(): void
    {
        $definitions = (new Listing())->load();

        foreach ($definitions as $definition) {
            if ($definition->getKey() === $this->getName()) {
                $this->definition = $definition;

                return;
            }
        }
    }
}
