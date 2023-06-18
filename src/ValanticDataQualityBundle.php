<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Valantic\DataQualityBundle\DependencyInjection\Compiler\SerializerPass;
use Valantic\DataQualityBundle\Installer\Installer;

class ValanticDataQualityBundle extends AbstractPimcoreBundle
{
    use PackageVersionTrait;

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new SerializerPass());
    }

    public function getJsPaths(): array
    {
        return [
            '/bundles/valanticdataquality/js/pimcore/objectView.js',
            '/bundles/valanticdataquality/js/pimcore/settingsConstraints.js',
            '/bundles/valanticdataquality/js/pimcore/settingsMeta.js',
            '/bundles/valanticdataquality/js/pimcore/settings.js',
            '/bundles/valanticdataquality/js/pimcore/startup.js',
            '/bundles/valanticdataquality/js/pimcore/objects/classes/data/valanticDataQualityScore.js',
            '/bundles/valanticdataquality/js/pimcore/objects/gridcolumn/operator/valanticDataQualityScore.js',
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
