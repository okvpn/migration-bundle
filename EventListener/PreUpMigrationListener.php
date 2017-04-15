<?php

namespace Okvpn\Bundle\MigrationBundle\EventListener;

use Okvpn\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Okvpn\Bundle\MigrationBundle\Migration\CreateMigrationTableMigration;

class PreUpMigrationListener
{
    /**
     * @param PreMigrationEvent $event
     */
    public function onPreUp(PreMigrationEvent $event)
    {
        if ($event->isTableExist(CreateMigrationTableMigration::MIGRATION_TABLE)) {
            $data = $event->getData(
                sprintf(
                    'select * from %s where id in (select max(id) from %s group by bundle)',
                    CreateMigrationTableMigration::MIGRATION_TABLE,
                    CreateMigrationTableMigration::MIGRATION_TABLE
                )
            );
            foreach ($data as $val) {
                $event->setLoadedVersion($val['bundle'], $val['version']);
            }
        } else {
            $event->addMigration(new CreateMigrationTableMigration());
        }
    }
}
