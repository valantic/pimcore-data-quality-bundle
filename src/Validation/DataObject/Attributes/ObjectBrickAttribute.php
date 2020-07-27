<?php

namespace Valantic\DataQualityBundle\Validation\DataObject\Attributes;

use Pimcore\Model\DataObject\Objectbrick;
use Pimcore\Model\DataObject\Objectbrick\Data\AbstractData;

class ObjectBrickAttribute extends AbstractAttribute
{
    /**
     * {@inheritDoc}
     */
    public function value()
    {
        [$attribute, $brick, $brickAttribute] = explode('.', $this->attribute, 3);
        $objAttr = $this->obj->get($attribute);
        if (!($objAttr instanceof Objectbrick)) {
            return null;
        }
        $brickAttr = $objAttr->{'get' . ucfirst($brick)}();

        if (!($brickAttr instanceof AbstractData)) {
            return null;
        }

        return $brickAttr->{'get' . ucfirst($brickAttribute)}();
    }
}
