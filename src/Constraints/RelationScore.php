<?php

namespace Valantic\DataQualityBundle\Constraints;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

class RelationScore extends AbstractCustomConstraint
{
    public $message = 'The related object score(s) fall below the threshold.';

    public $expected;

    public $allowed;

    /**
     * @var ContainerInterface
     */
    public $container;

    /**
     * {@inheritDoc}
     */
    public function validatedBy()
    {
        return RelationScoreValidator::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return 'RelationScore';
    }

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
