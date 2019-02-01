<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MigrationBundle\EventListener;

use Doctrine\DBAL\Event\SchemaAlterTableChangeColumnEventArgs;

/**
 * In order to disable update migration table.
 */
final class DoctrineSchemaChangeListener
{
    /** @var string */
    private $migrationTable;

    /**
     * @param string $migrationTable
     */
    public function __construct(string $migrationTable)
    {
        $this->migrationTable = $migrationTable;
    }

    /**
     * @param SchemaAlterTableChangeColumnEventArgs $args
     */
    public function onSchemaAlterTableChangeColumn(SchemaAlterTableChangeColumnEventArgs $args)
    {
        if ($args->getTableDiff()->name === $this->migrationTable) {
            $args->preventDefault();
        }
    }
}
