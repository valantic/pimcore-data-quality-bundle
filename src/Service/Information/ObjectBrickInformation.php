<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Service\Information;

use Pimcore\Model\DataObject\Objectbrick\Definition;
use Pimcore\Model\DataObject\Objectbrick\Definition\Listing;

class ObjectBrickInformation extends AbstractDefinitionInformation
{
    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore Has to be mocked
     */
    public function getDefinition(): ?Definition
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
