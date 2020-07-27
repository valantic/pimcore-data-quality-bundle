<?php

namespace Valantic\DataQualityBundle\Installer;

use Doctrine\DBAL\Migrations\Version;
use Doctrine\DBAL\Schema\Schema;
use Pimcore\Db;
use Pimcore\Db\ConnectionInterface;
use Pimcore\Extension\Bundle\Installer\MigrationInstaller;
use Pimcore\Migrations\MigrationManager;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Valantic\DataQualityBundle\Config\V1\Constraints\Writer as ConfigWriter;
use Valantic\DataQualityBundle\Controller\ConstraintConfigController;

class Installer extends MigrationInstaller
{
    /**
     * @var ConfigWriter
     */
    protected $writer;

    public function __construct(BundleInterface $bundle, ConnectionInterface $connection, MigrationManager $migrationManager, ConfigWriter $writer)
    {
        parent::__construct($bundle, $connection, $migrationManager);
        $this->writer = $writer;
    }

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
        $check = $db->fetchOne("SELECT `key` FROM `users_permission_definitions` where `key` = ?", [ConstraintConfigController::CONFIG_NAME]);

        return (bool)$check;
    }

    /**
     * {@inheritdoc}
     */
    public function migrateInstall(Schema $schema, Version $version): void
    {
        $version->addSql('INSERT INTO `users_permission_definitions` (`key`) VALUES (?);', [ConstraintConfigController::CONFIG_NAME]);
        $this->writer->ensureConfigExists();
    }

    /**
     * {@inheritdoc}
     */
    public function migrateUninstall(Schema $schema, Version $version): void
    {
        $version->addSql('DELETE FROM `users_permission_definitions` WHERE `key` = ?;', [ConstraintConfigController::CONFIG_NAME]);
    }
}
