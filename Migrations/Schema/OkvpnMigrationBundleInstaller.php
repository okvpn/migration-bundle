<?php

namespace Okvpn\Bundle\MigrationBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Okvpn\Bundle\MigrationBundle\Migration\Installation;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

class OkvpnMigrationBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOkvpnMigrationsDataTable($schema);
    }

    /**
     * Create okvpn_migrations_data table
     *
     * @param Schema $schema
     */
    protected function createOkvpnMigrationsDataTable(Schema $schema)
    {
        $table = $schema->createTable('okvpn_migrations_data');
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('class_name', 'string', ['default' => null, 'notnull' => true, 'length' => 255]);
        $table->addColumn('loaded_at', 'datetime', ['notnull' => true]);
        $table->addColumn('version', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }
}
