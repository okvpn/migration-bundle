<?php

namespace Okvpn\Bundle\MigrationBundle\Provider;

use Doctrine\DBAL\Migrations\Provider\SchemaProviderInterface;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ColumnDiff;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Doctrine\DBAL\Schema\Sequence;

use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Okvpn\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;
use Okvpn\Bundle\MigrationBundle\Migration\MigrationState;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;
use Okvpn\Bundle\MigrationBundle\Migration\Schema\Table;

class OkvpnSchemaProvider implements SchemaProviderInterface
{
    /** @var MigrationsLoader */
    protected $migrationsLoader;

    /**
     * @param MigrationsLoader $migrationsLoader
     */
    public function __construct(MigrationsLoader $migrationsLoader)
    {
        $this->migrationsLoader = $migrationsLoader;
    }

    /**
     * @inheritDoc
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

//        $t = new \Doctrine\DBAL\Schema\Table();
//        $x = new TableDiff('xxx');
//        $x->removedColumns
//        $f = new Column('ddd','ddd');
//        $f->getName()
//        //$f->setOptions()
//        $t->dropColumn()
//        $z = new ColumnDiff('xxx', new Column('ccc', Type::BIGINT));
//        $z->column->getOp
////$x->removedForeignKeys
//        //$z->changedProperties;
//        //$f->set



        return $toSchema;
    }

    /**
     * Creates a database schema object
     *
     * @param Table[] $tables
     * @param Sequence[] $sequences
     * @param SchemaConfig|null $schemaConfig
     *
     * @return Schema
     */
    protected function createSchemaObject(array $tables = [], array $sequences = [], $schemaConfig = null)
    {
        return new Schema($tables, $sequences, $schemaConfig);
    }
}
