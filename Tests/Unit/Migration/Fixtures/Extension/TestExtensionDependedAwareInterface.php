<?php

namespace Okvpn\Bundle\MigrationBundle\Tests\Unit\Migration\Fixtures\Extension;

interface TestExtensionDependedAwareInterface
{
    public function setTestExtensionDepended(
        TestExtensionDepended $testExtensionDepended
    );
}
