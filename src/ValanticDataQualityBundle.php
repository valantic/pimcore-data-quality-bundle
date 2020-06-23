<?php

namespace Valantic\DataQualityBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Valantic\DataQualityBundle\Installer\Installer;

class ValanticDataQualityBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    /**
     * {@inheritdoc}
     */
    protected function getComposerPackageName(): string
    {
        return 'valantic-pimcore/data-quality-bundle';
    }

    /**
     * {@inheritdoc}
     */
    public function getJsPaths(): array
    {
        return [
            '/bundles/valanticdataquality/js/pimcore/constraints.js',
            '/bundles/valanticdataquality/js/pimcore/locales.js',
            '/bundles/valanticdataquality/js/pimcore/object_view.js',
            '/bundles/valanticdataquality/js/pimcore/startup.js',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return InstallerInterface|null
     */
    public function getInstaller()
    {
        return $this->container->get(Installer::class);
    }
}
