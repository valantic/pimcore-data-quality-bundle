<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as SymfonyKernel;

class Kernel extends SymfonyKernel
{
    /**
     * {@inheritDoc}
     */
    public function registerBundles()
    {
        return [
            new FrameworkBundle(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        // TODO: Implement registerContainerConfiguration() method.
    }
}
