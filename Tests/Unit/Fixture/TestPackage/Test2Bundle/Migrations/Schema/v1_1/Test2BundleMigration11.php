<?php

namespace Okvpn\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Okvpn\Bundle\MigrationBundle\Migration\Migration;
use Okvpn\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;

class Test2BundleMigration11 implements Migration, OrderedMigrationInterface
{
    public function getOrder()
    {
        return 2;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('test1table');
        $table->addColumn('another_column', 'int');
    }
}
