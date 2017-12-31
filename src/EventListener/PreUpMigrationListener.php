<?php

declare(strict_types = 1);

namespace Okvpn\Bundle\MigrationBundle\EventListener;

use Okvpn\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Okvpn\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;

class PreUpMigrationListener
{
    private $migrationTable;

    /**
     * @param string $migrationTable
     */
    public function __construct(string $migrationTable)
    {
        $this->migrationTable = $migrationTable;
    }

    /**
     * @param PreMigrationEvent $event
     */
    public function onPreUp(PreMigrationEvent $event)
    {
        if ($event->isTableExist($this->migrationTable)) {
            $data = $event->getData(
                sprintf(
                    'select * from %s where id in (select max(id) from %s group by bundle)',
                    $this->migrationTable,
                    $this->migrationTable
                )
            );
            foreach ($data as $val) {
                $event->setLoadedVersion($val['bundle'], $val['version']);
            }
        } else {
            $event->addMigration(new CreateMigrationTableMigration($this->migrationTable));
        }
    }
}
