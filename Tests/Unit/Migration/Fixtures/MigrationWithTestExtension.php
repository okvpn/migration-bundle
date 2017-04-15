<?php

namespace Okvpn\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Okvpn\Bundle\MigrationBundle\Migration\Migration;
use Okvpn\Bundle\MigrationBundle\Migration\QueryBag;
use Okvpn\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Okvpn\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Okvpn\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;
use Okvpn\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtension;
use Okvpn\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension\TestExtensionAwareInterface;

class MigrationWithTestExtension implements
    Migration,
    TestExtensionAwareInterface,
    DatabasePlatformAwareInterface,
    NameGeneratorAwareInterface
{
    protected $testExtension;

    protected $platform;

    protected $nameGenerator;

    public function setTestExtension(TestExtension $testExtension)
    {
        $this->testExtension = $testExtension;
    }

    public function getTestExtension()
    {
        return $this->testExtension;
    }

    public function setDatabasePlatform(AbstractPlatform $platform)
    {
        $this->platform = $platform;
    }

    public function getDatabasePlatform()
    {
        return $this->platform;
    }

    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    public function getNameGenerator()
    {
        return $this->nameGenerator;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
    }
}
