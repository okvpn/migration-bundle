<?php

namespace Okvpn\Bundle\MigrationBundle\Provider;

use Doctrine\DBAL\Schema\Schema;

use Okvpn\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;
use Okvpn\Bundle\MigrationBundle\Migration\Migration;
use Okvpn\Bundle\MigrationBundle\Migration\MigrationExtensionManager;
use Okvpn\Bundle\MigrationBundle\Migration\MigrationQueryExecutor;
use Okvpn\Bundle\MigrationBundle\Migration\MigrationState;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * A schema provider that uses the current migrations to generate schemas.
 */
final class MigrationSchemaProvider implements SchemaProviderInterface
{
    /** @var MigrationsLoader */
    private $migrationsLoader;

    /** @var MigrationExtensionManager */
    private $extensionManager;

    /** @var MigrationQueryExecutor */
    private $queryExecutor;

    /**
     * @param MigrationsLoader $migrationsLoader
     */
    public function __construct(MigrationsLoader $migrationsLoader, MigrationQueryExecutor $queryExecutor)
    {
        $this->migrationsLoader = $migrationsLoader;
        $this->queryExecutor = $queryExecutor;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $migrations = $this->migrationsLoader->getPlainMigrations();
        $toSchema = new Schema();
        $queryBag = new QueryBag();
        $this->queryExecutor->getConnection()->beginTransaction();
        try {
            /** @var MigrationState $migrationState */
            foreach ($migrations as $migrationState) {
                $migration = $migrationState->getMigration();
                $this->setExtensions($migration);
                $migration->up($toSchema, $queryBag);
            }
        } catch (\Throwable $exception) {
            throw $exception;
        } finally {
            $this->queryExecutor->getConnection()->rollBack();
        }

        return $toSchema;
    }

    /**
     * Sets extension manager
     *
     * @param MigrationExtensionManager $extensionManager
     */
    public function setExtensionManager(MigrationExtensionManager $extensionManager)
    {
        $this->extensionManager = $extensionManager;
        $connection = $this->queryExecutor->getConnection();
        $this->extensionManager->setConnection($connection);
        $this->extensionManager->setDatabasePlatform($connection->getDatabasePlatform());
    }

    /**
     * Sets extensions for the given migration
     *
     * @param Migration $migration
     */
    protected function setExtensions(Migration $migration)
    {
        if ($this->extensionManager) {
            $this->extensionManager->applyExtensions($migration);
        }
    }
}
