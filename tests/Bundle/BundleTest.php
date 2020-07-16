<?php

namespace Valantic\DataQualityBundle\Tests\Bundle;

use Valantic\DataQualityBundle\Tests\AbstractTestCase;
use Valantic\DataQualityBundle\ValanticDataQualityBundle;

class BundleTest extends AbstractTestCase
{
    /**
     * @var ValanticDataQualityBundle
     */
    protected $bundle;

    protected function setUp(): void
    {
        $this->bundle = new ValanticDataQualityBundle();
    }

    public function testJsPaths()
    {
        $this->assertIsArray($this->bundle->getJsPaths());

        $prefix = '/bundles/valanticdataquality';
        $basepath = __DIR__ . '/../src/Resources/public';

        foreach ($this->bundle->getJsPaths() as $path) {
            $this->assertFileExists($basepath . str_replace($prefix, '', $path));
        }
    }
}
