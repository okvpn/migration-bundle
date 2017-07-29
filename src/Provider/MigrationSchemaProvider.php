<?php

namespace Okvpn\Bundle\MigrationBundle\Provider;

use Doctrine\DBAL\Schema\Schema;

use Okvpn\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;
use Okvpn\Bundle\MigrationBundle\Migration\MigrationState;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * A schema provider that uses the current migrations to generate schemas.
 */
final class MigrationSchemaProvider implements SchemaProviderInterface
{
    /** @var MigrationsLoader */
    private $migrationsLoader;

    /**
     * @param MigrationsLoader $migrationsLoader
     */
    public function __construct(MigrationsLoader $migrationsLoader)
    {
        $this->migrationsLoader = $migrationsLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function createSchema()
    {
        $migrations = $this->migrationsLoader->getPlainMigrations();
        $toSchema = new Schema();
        $queryBag = new QueryBag();
        /** @var MigrationState $migrationState */
        foreach ($migrations as $migrationState) {
            $migrationState->getMigration()->up($toSchema, $queryBag);
        }

        return $toSchema;
    }
}
