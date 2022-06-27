<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;
use Pimcore\Model\DataObject\ClassDefinition\Listing as ClassDefinitionListing;

abstract class BaseController extends AdminController
{
    public const CONFIG_NAME = 'plugin_valantic_dataquality_config';

    public function getClassNames(): array
    {
        $classesList = new ClassDefinitionListing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();

        return array_column($classes, 'name');
    }

    protected function checkPermission($permission): void
    {
        /**
         * Due to the way parent::checkPermission() works, this call is workaround
         * to properly test the controller actions.
         */
        if (defined('PHPUNIT_SKIP_PIMCORE_PERMISSION_CHECK') && PHPUNIT_SKIP_PIMCORE_PERMISSION_CHECK) {
            return;
        }

        parent::checkPermission($permission);
    }
}
