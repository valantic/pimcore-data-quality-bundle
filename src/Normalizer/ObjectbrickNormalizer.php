<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Normalizer;

use Pimcore\Model\DataObject;

class ObjectbrickNormalizer extends AbstractNormalizer
{
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return
            parent::supportsNormalization($data, $format, $context)
            && $data instanceof DataObject\Objectbrick;
    }

    public function normalize($object, ?string $format = null, array $context = []): mixed
    {
        $parts = explode('.', (string) $context['resource_attribute'], 3);
        $getter = 'get' . ucfirst($parts[1]);
        $objectBrickData = null;
        if (method_exists($object, $getter)) {
            $objectBrickData = $object->get($parts[1]);
        }

        if ($objectBrickData !== null) {
            $getter = 'get' . ucfirst($parts[2]);
            if (method_exists($objectBrickData, $getter)) {
                return $this->normalizer->normalize($objectBrickData->get($parts[2]), $format, $context);
            }
        }

        return null;
    }
}
