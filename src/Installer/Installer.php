<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Installer;

use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;
use Valantic\DataQualityBundle\Config\V1\Constraints\Writer as ConfigWriter;
use Valantic\DataQualityBundle\Controller\BaseController;

class Installer extends AbstractInstaller
{
    public function __construct(
        protected ConfigWriter $writer
    ) {
        parent::__construct();
    }

    public function getMigrationVersion(): string
    {
        return '20200618112517';
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    public function isInstalled(): bool
    {
        $db = Db::get();
        $check = $db->fetchOne(
            'SELECT `key` FROM `users_permission_definitions` where `key` = ?',
            [BaseController::CONFIG_NAME]
        );

        return (bool) $check;
    }

    public function install(): void
    {
        $db = Db::get();
        $db->executeStatement(
            'INSERT INTO `users_permission_definitions` (`key`) VALUES (?);',
            [BaseController::CONFIG_NAME]
        );
        $this->writer->ensureConfigExists();
    }

    public function uninstall(): void
    {
        $db = Db::get();
        $db->executeStatement(
            'DELETE FROM `users_permission_definitions` WHERE `key` = ?;',
            [BaseController::CONFIG_NAME]
        );
    }
}
