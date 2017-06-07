<?php

namespace Okvpn\Bundle\MigrationBundle\Event;

class MigrationEvents
{
    /**
     * This event is raised before a list of migrations are built.
     * You can use it to add additional migrations to the beginning of the migration list.
     *
     * @var string
     */
    const PRE_UP = 'okvpn_migration.pre_up';

    /**
     * This event is raised after a list of migrations are built.
     * You can use it to add additional migrations to the end of the migration list.
     *
     * @var string
     */
    const POST_UP = 'okvpn_migration.post_up';

    /**
     * This event is raised before data fixtures are loaded.
     *
     * @var string
     */
    const DATA_FIXTURES_PRE_LOAD = 'okvpn_migration.data_fixtures.pre_load';

    /**
     * This event is raised after data fixtures are loaded.
     *
     * @var string
     */
    const DATA_FIXTURES_POST_LOAD = 'okvpn_migration.data_fixtures.post_load';
}
