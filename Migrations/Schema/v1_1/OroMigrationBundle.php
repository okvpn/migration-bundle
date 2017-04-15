<?php

namespace Okvpn\Bundle\MigrationBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Okvpn\Bundle\MigrationBundle\Migration\Migration;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

class OroMigrationBundle implements Migration
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_migrations_data');
        $table->addColumn('version', 'string', ['notnull' => false, 'length' => 255]);
    }
}
