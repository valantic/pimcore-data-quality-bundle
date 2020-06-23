<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;

abstract class BaseController extends AdminController
{
    public const CONFIG_NAME = 'plugin_valantic_dataquality_config';

    protected function getClassNames():array{
        $classesList = new ClassDefinitionListing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();

        return array_column($classes, 'name');
    }
}
