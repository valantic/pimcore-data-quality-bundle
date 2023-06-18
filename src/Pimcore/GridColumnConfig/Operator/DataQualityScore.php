<?php

namespace Valantic\DataQualityBundle\Pimcore\GridColumnConfig\Operator;

use Pimcore\Cache;
use Pimcore\DataObject\GridColumnConfig\Operator\AbstractOperator;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Valantic\DataQualityBundle\Service\CacheService;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

class DataQualityScore extends AbstractOperator
{
    public function __construct(
        protected Validate $validation,
        \stdClass $config,
        array $context = [],
    ) {
        parent::__construct($config, $context);
    }

    public function getLabeledValue($element): \stdClass|null
    {
        $result = new \stdClass();
        $result->label = $this->label;
        $result->value = $this->getScore($element);

        return $result;
    }

    private function getScore(array|ElementInterface $element): string
    {
        if (!$element instanceof Concrete) {
            return '0 %';
        }

        $cacheKey = CacheService::getScoreCacheKey((int) $element->getId());
        $scores = Cache::load($cacheKey);

        $this->validation->setObject($element);
        if ($scores === false) {
            $this->validation->validate();
        }

        $scores = $this->validation->scores();
        $score = $scores[$this->context['language']] ?? 0;

        return sprintf('%d %%', (int) round($score * 100));
    }
}
