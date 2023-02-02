<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\EventListener;

use Symfony\Component\EventDispatcher\GenericEvent;
use Valantic\DataQualityBundle\Repository\ConfigurationRepository;
use Valantic\DataQualityBundle\Repository\DataObjectRepository;
use Valantic\DataQualityBundle\Service\Formatters\PercentageFormatter;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

class AdminObjectListListener extends AbstractListener
{
    public function __construct(
        private ConfigurationRepository $configurationRepository,
        private DataObjectRepository $dataObjectRepository,
        private Validate $validation,
        private PercentageFormatter $percentageFormatter,
    ) {
    }

    public function handle(GenericEvent $event): void
    {
        if (!self::$isEnabled) {
            return;
        }

        $list = $event->getArgument('list');

        if ($list && property_exists($list, 'className')) {
            /** @var class-string $className */
            $className = DataObjectRepository::PIMCORE_DATA_OBJECT_NAMESPACE . '\\' . $list->getClassName();

            $fieldName = $this->configurationRepository->getScoreFieldName($className);

            if (empty($fieldName)) {
                return;
            }

            $ignoreFallbackLanguage = $this->configurationRepository->getIgnoreFallbackLanguage($className);

            $objects = $list->getObjects();

            foreach ($objects as $obj) {
                if (property_exists($obj, $fieldName) && $obj->getPublished()) {
                    $this->validation->setIgnoreFallbackLanguage($ignoreFallbackLanguage);
                    $this->validation->setObject($obj);
                    $this->validation->validate();

                    $score = $this->percentageFormatter->format($this->validation->score());
                    $this->dataObjectRepository->setValue($obj, $fieldName, $score);
                }
            }
        }
    }
}
