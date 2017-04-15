<?php
namespace Okvpn\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test3Bundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Okvpn\Bundle\MigrationBundle\Fixture\VersionedFixtureInterface;

class LoadTest3BundleData1 extends AbstractFixture implements VersionedFixtureInterface, OrderedFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersion()
    {
        return '1.0';
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
