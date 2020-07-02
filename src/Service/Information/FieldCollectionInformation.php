<?php

namespace Valantic\DataQualityBundle\Service\Information;

use Pimcore\Model\DataObject\Fieldcollection\Definition\Listing;

class FieldCollectionInformation extends DefinitionInformation
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
