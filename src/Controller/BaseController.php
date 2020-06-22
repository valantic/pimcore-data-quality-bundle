<?php

namespace Valantic\DataQualityBundle\Controller;

use Pimcore\Bundle\AdminBundle\Controller\AdminController;

abstract class BaseController extends AdminController
{
    public const CONFIG_NAME = 'plugin_valantic_dataquality_config';
}
