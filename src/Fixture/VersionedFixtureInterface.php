<?php

namespace Okvpn\Bundle\MigrationBundle\Fixture;

/**
 * @deprecated since 1.2 and will be removed in 1.3. Fixtures package will be moved to another separate repository,
 *             it step needed to reduces the count of not necessary dependencies
 */
interface VersionedFixtureInterface
{
    /**
     * Return current fixture version
     *
     * @return string
     */
    public function getVersion();
}
