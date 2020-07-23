<?php

namespace Valantic\DataQualityBundle\Constraints\RelationScore;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Valantic\DataQualityBundle\Repository\AbstractCustomConstraint;

abstract class AbstractConstraint extends AbstractCustomConstraint
{
    public $message = 'The related object score(s) fall below the threshold.';

    public $expected;

    public $allowed;

    /**
     * @var ContainerInterface
     */
    public $container;

    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
