<?php

namespace Okvpn\Bundle\MigrationBundle\Tests\Unit\Migration;

use Okvpn\Bundle\MigrationBundle\Migration\SqlSchemaUpdateMigrationQuery;

class SqlSchemaUpdateMigrationQueryTest extends \PHPUnit_Framework_TestCase
{
    public function testIsUpdateRequired()
    {
        $query = new SqlSchemaUpdateMigrationQuery('ALTER TABLE');

        $this->assertInstanceOf('Okvpn\Bundle\MigrationBundle\Migration\SqlMigrationQuery', $query);
        $this->assertInstanceOf('Okvpn\Bundle\MigrationBundle\Migration\SchemaUpdateQuery', $query);
        $this->assertTrue($query->isUpdateRequired());
    }
}
