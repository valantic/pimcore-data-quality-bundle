<?php

namespace Valantic\DataQualityBundle\Pimcore\GridColumnConfig\Operator\Factory;

use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\Factory\OperatorFactoryInterface;
use Pimcore\Bundle\AdminBundle\DataObject\GridColumnConfig\Operator\OperatorInterface;
use Valantic\DataQualityBundle\Pimcore\GridColumnConfig\Operator\DataQualityScore;
use Valantic\DataQualityBundle\Validation\DataObject\Validate;

class DataQualityScoreFactory implements OperatorFactoryInterface
{
    public function __construct(
        private readonly Validate $validation,
    ) {
    }

    public function build(\stdClass $configElement, array $context = []): ?OperatorInterface
    {
        return new DataQualityScore($this->validation, $configElement, $context);
    }
}
