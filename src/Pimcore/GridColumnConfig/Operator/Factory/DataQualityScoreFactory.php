<?php

namespace Valantic\DataQualityBundle\Pimcore\GridColumnConfig\Operator\Factory;

use Pimcore\DataObject\GridColumnConfig\Operator\Factory\OperatorFactoryInterface;
use Valantic\DataQualityBundle\Pimcore\GridColumnConfig\Operator\DataQualityScore;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

class DataQualityScoreFactory implements OperatorFactoryInterface
{
    public function __construct(private Validate $validation)
    {
    }

    public function build(\stdClass $configElement, array $context = []): DataQualityScore
    {
        return new DataQualityScore($this->validation, $configElement, $context);
    }
}
