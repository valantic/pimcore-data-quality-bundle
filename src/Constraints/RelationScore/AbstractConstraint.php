<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

abstract class AbstractConstraint extends AbstractCustomConstraint
{
    /**
     * @var string
     */
    public $message = 'The related object score(s) fall below the threshold.';

    /**
     * @var ContainerInterface
     */
    public $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }
}
