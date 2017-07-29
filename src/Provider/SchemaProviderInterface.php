<?php

namespace Okvpn\Bundle\MigrationBundle\Provider;

interface SchemaProviderInterface
{
    /**
     * Create the schema to which the database should be migrated.
     *
     * @return  \Doctrine\DBAL\Schema\Schema
     */
    public function createSchema();
}
