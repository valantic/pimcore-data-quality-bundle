<?php

namespace Valantic\DataQualityBundle\Tests;

abstract class AbstractTestCase extends \PHPUnit\Framework\TestCase
{
    public const CONFIG_FULL = 'config_full.yml';
    public const CONFIG_CORRUPT = 'config_corrupt.yml';

    public static function setUpBeforeClass(): void
    {
        static::cleanUp();
    }

    protected static function cleanUp()
    {
        array_map('unlink', array_filter((array)glob(__DIR__ . '/tmp/*')?:[]));
    }

    protected function activateConfig(string $name)
    {
        copy(__DIR__ . '/fixtures/' . $name, __DIR__ . '/tmp/valantic_dataquality_config.yml');
    }
}
