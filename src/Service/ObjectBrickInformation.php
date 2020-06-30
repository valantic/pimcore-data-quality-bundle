<?php

namespace Valantic\DataQualityBundle\Service;

use Pimcore\Model\AbstractModel;
use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing;

class ObjectBrickInformation extends DefinitionInformation
{

    /**
     * Get the definition of the class.
     * @return Definition|null
     */
    protected function getDefinition(): ?AbstractModel
    {
        $definitions = (new Listing())->load();

        foreach ($definitions as $definition) {
            if ($definition->getKey() === $this->getName()) {
                return $definition;
            }
        }

        return null;
    }
}
