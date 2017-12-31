<?php

declare(strict_types = 1);

namespace Okvpn\Bundle\MigrationBundle\Migration;

use Doctrine\DBAL\Schema\Schema;

class CreateMigrationTableMigration implements Migration
{
    const MIGRATION_TABLE = 'okvpn_migrations';

    /**
     * @var string
     */
    protected $migrationTable = self::MIGRATION_TABLE;

    /**
     * @param null|string $migrationTable
     */
    public function __construct(string $migrationTable = null)
    {
        if ($migrationTable !== null) {
            $this->migrationTable = $migrationTable;
        }
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable($this->migrationTable);
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('bundle', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('version', 'string', ['notnull' => true, 'length' => 250]);
        $table->addColumn('loaded_at', 'datetime', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['bundle'], 'idx_' . $this->migrationTable, []);
    }
}
