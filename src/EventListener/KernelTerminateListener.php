<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\EventListener;

use Valantic\DataQualityBundle\Repository\ConfigurationRepository;

class KernelTerminateListener
{
    public function __construct(protected ConfigurationRepository $configurationRepository)
    {
    }

    public function __invoke(): void
    {
        $this->configurationRepository->persist();
    }
}
