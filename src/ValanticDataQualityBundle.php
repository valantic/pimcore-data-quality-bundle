<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Valantic\DataQualityBundle\Installer\Installer;

class ValanticDataQualityBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function getJsPaths(): array
    {
        return [
            '/bundles/valanticdataquality/js/pimcore/objectView.js',
            '/bundles/valanticdataquality/js/pimcore/settingsConstraints.js',
            '/bundles/valanticdataquality/js/pimcore/settingsMeta.js',
            '/bundles/valanticdataquality/js/pimcore/settings.js',
            '/bundles/valanticdataquality/js/pimcore/startup.js',
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore Can't be executed in testing
     */
    public function getInstaller(): Installer
    {
        return $this->container->get(Installer::class);
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore
     */
    protected function getComposerPackageName(): string
    {
        return 'valantic/pimcore-data-quality-bundle';
    }
}
