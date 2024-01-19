<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Installer;

use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\SettingsStoreAwareInstaller;
use Valantic\DataQualityBundle\Controller\BaseController;

class Installer extends SettingsStoreAwareInstaller
{
    public function install(): void
    {
        $db = Db::get();
        $db->executeStatement(
            'INSERT INTO `users_permission_definitions` (`key`) VALUES (?);',
            [BaseController::CONFIG_NAME]
        );

        parent::install();
    }

    public function uninstall(): void
    {
        $db = Db::get();
        $db->executeStatement(
            'DELETE FROM `users_permission_definitions` WHERE `key` = ?;',
            [BaseController::CONFIG_NAME]
        );

        parent::uninstall();
    }
}
