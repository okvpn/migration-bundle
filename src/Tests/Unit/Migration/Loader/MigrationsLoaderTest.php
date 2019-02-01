<?php

namespace Okvpn\Bundle\MigrationBundle\Tests\Unit\Migration\Loader;

use Okvpn\Bundle\MigrationBundle\Event\MigrationEvents;
use Okvpn\Bundle\MigrationBundle\Event\PreMigrationEvent;
use Okvpn\Bundle\MigrationBundle\Migration\MigrationState;
use Okvpn\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test1Bundle\TestPackageTest1Bundle;
use Okvpn\Bundle\MigrationBundle\Tests\Unit\Fixture\TestPackage\Test2Bundle\TestPackageTest2Bundle;

use Okvpn\Bundle\MigrationBundle\Migration\Loader\MigrationsLoader;

class MigrationsLoaderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $kernel;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    protected function setUp()
    {
        $this->kernel          = $this->getMockBuilder('Symfony\Component\HttpKernel\Kernel')
            ->disableOriginalConstructor()
            ->getMock();
        $this->container       = $this->getMockForAbstractClass(
            'Symfony\Component\DependencyInjection\ContainerInterface'
        );
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider getMigrationsProvider
     */
    public function testGetMigrations($bundles, $installed, $expectedMigrationClasses)
    {
        $bundlesList = [];

        /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
        foreach ($bundles as $bundle) {
            $bundlesList[$bundle->getName()] = [
                'dir_name' => $bundle->getPath(),
                'namespace' => $bundle->getNamespace(),
            ];
        }

        $loader = new MigrationsLoader(
            $this->kernel,
            $this->connection,
            $this->container,
            $this->eventDispatcher,
            'Migrations/Schema',
            'okvpn_migrations',
            $bundlesList
        );

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->will(
                $this->returnCallback(
                    function ($eventName, $event) use (&$installed) {
                        if ($eventName === MigrationEvents::PRE_UP) {
                            if (null !== $installed) {
                                foreach ($installed as $val) {
                                    /** @var PreMigrationEvent $event */
                                    $event->setLoadedVersion($val['bundle'], $val['version']);
                                }
                            }
                        }
                    }
                )
            );

        $migrations       = $loader->getMigrations();
        $migrationClasses = $this->getMigrationClasses($migrations);
        $this->assertEquals($expectedMigrationClasses, $migrationClasses);
    }

    /**
     * @param MigrationState[] $migrations
     *
     * @return string[]
     */
    protected function getMigrationClasses(array $migrations)
    {
        return array_map(
            function ($migration) {
                return get_class($migration->getMigration());
            },
            $migrations
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getMigrationsProvider()
    {
        $testPackage = 'Okvpn\\Bundle\\MigrationBundle\\Tests\\Unit\\Fixture\\TestPackage\\';
        $test1Bundle = $testPackage . 'Test1Bundle\\Migrations\\Schema';
        $test2Bundle = $testPackage . 'Test2Bundle\\Migrations\\Schema';

        return [
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                null,
                [
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest2Bundle(), new TestPackageTest1Bundle()],
                null,
                [
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    'Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [],
                [
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => null],
                ],
                [
                    $test1Bundle . '\Test1BundleInstallation',
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_0'],
                ],
                [
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_0\Test2BundleMigration10',
                    $test2Bundle . '\v1_0\Test2BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_0'],
                    ['bundle' => 'TestPackageTest2Bundle', 'version' => 'v1_0'],
                ],
                [
                    $test1Bundle . '\v1_1\Test1BundleMigration11',
                    $test2Bundle . '\v1_1\Test2BundleMigration12',
                    $test2Bundle . '\v1_1\Test2BundleMigration11',
                    'Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
            [
                [new TestPackageTest1Bundle(), new TestPackageTest2Bundle()],
                [
                    ['bundle' => 'TestPackageTest1Bundle', 'version' => 'v1_1'],
                    ['bundle' => 'TestPackageTest2Bundle', 'version' => 'v1_1'],
                ],
                [
                    'Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration',
                ]
            ],
        ];
    }
}
