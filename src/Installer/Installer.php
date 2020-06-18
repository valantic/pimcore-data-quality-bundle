<?php

namespace Valantic\DataQualityBundle\Installer;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Valantic\DataQualityBundle\Controller\ConfigController;

class Installer extends MigrationInstaller
{

    public function getMigrationVersion(): string
    {
        return '20200618112517';
    }

    public function needsReloadAfterInstall(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    /**
     * {@inheritdoc}
     */
    public function isInstalled(): bool
    {
        $db = Db::get();
        $check = $db->fetchOne("SELECT `key` FROM `users_permission_definitions` where `key` = ?", [ConfigController::CONFIG_NAME]);

        return (bool)$check;
    }

    /**
     * {@inheritdoc}
     */
    public function migrateInstall(Schema $schema, Version $version)
    {
        $version->addSql('INSERT INTO `users_permission_definitions` (`key`) VALUES (?);', [ConfigController::CONFIG_NAME]);
    }

    /**
     * {@inheritdoc}
     */
    public function migrateUninstall(Schema $schema, Version $version)
    {
        $version->addSql('DELETE FROM `users_permission_definitions` WHERE `key` = ?;', [ConfigController::CONFIG_NAME]);
    }
}
