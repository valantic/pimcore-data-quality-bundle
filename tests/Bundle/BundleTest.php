<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Tests\Bundle;

use Pimcore\Routing\RouteReference;
use Valantic\DataQualityBundle\Tests\AbstractTestCase;
use Valantic\DataQualityBundle\ValanticDataQualityBundle;

class BundleTest extends AbstractTestCase
{
    protected ValanticDataQualityBundle $bundle;

    protected function setUp(): void
    {
        $this->bundle = new ValanticDataQualityBundle();
    }

    public function testJsPaths(): void
    {
        $this->assertIsArray($this->bundle->getJsPaths());

        $prefix = '/bundles/valanticdataquality';
        $basepath = __DIR__ . '/../../src/Resources/public';

        foreach ($this->bundle->getJsPaths() as $path) {
            $path = $path instanceof RouteReference ? $path->getRoute() : $path;
            /** @var string $path */
            $this->assertFileExists(PIMCORE_WEB_ROOT . str_replace($prefix, '', $path));
        }
    }
}
