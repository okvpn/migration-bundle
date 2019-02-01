<?php

namespace Okvpn\Bundle\MigrationBundle\Migration\Loader;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Okvpn\Bundle\MigrationBundle\Migration\MigrationState;
use Okvpn\Bundle\MigrationBundle\Migration\Installation;
use Okvpn\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Okvpn\Bundle\MigrationBundle\Migration\UpdateBundleVersionMigration;
use Okvpn\Bundle\MigrationBundle\Event\MigrationEvents;
use Okvpn\Bundle\MigrationBundle\Event\PostMigrationEvent;
use Okvpn\Bundle\MigrationBundle\Event\PreMigrationEvent;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class MigrationsLoader
{
    /**
     * @var KernelInterface
     *
     */
    protected $kernel;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string An array with already loaded bundle migration versions
     *             key =   bundle name
     *             value = latest loaded version
     */
    protected $loadedVersions;

    /**
     * @var array An array with bundles we must work from
     */
    protected $bundles;

    /**
     * @var array An array with excluded bundles
     */
    protected $excludeBundles;

    /**
     * Prefix that located migration files. By default "Migrations/Schema"
     *
     * @var string
     */
    protected $migrationPath;

    /**
     * @var string Migration table
     */
    protected $migrationTable;

    /**
     * All migration paths to lookup bundles
     *
     * [
     *    'OkvpnBundle' => ["dir_name" => '/var/src/', "namespace" => 'OkvpnBundle/Migrations'],
     *    'AppBundle' => ["dir_name" => '/var/vendor/app/src', "namespace" => 'OkvpnBundle/Migrations'],
     * ]
     *
     * @var array
     */
    protected $migrationsPaths;

    /**
     * @param KernelInterface          $kernel
     * @param Connection               $connection
     * @param ContainerInterface       $container
     * @param EventDispatcherInterface $eventDispatcher
     * @param string                   $migrationPath
     * @param string                   $migrationTable
     * @param array                    $migrationsPaths
     */
    public function __construct(
        KernelInterface $kernel,
        Connection $connection,
        ContainerInterface $container,
        EventDispatcherInterface $eventDispatcher,
        string $migrationPath,
        string $migrationTable,
        array $migrationsPaths = []
    ) {
        $this->kernel          = $kernel;
        $this->connection      = $connection;
        $this->container       = $container;
        $this->eventDispatcher = $eventDispatcher;
        $this->migrationTable  = $migrationTable;
        $this->migrationPath   = $migrationPath;
        $this->migrationsPaths = $migrationsPaths;
    }

    /**
     * @param array $bundles
     */
    public function setBundles(array $bundles)
    {
        $this->bundles = $bundles;
    }

    /**
     * @param array $excludeBundles
     */
    public function setExcludeBundles(array $excludeBundles)
    {
        $this->excludeBundles = $excludeBundles;
    }

    /**
     * @return MigrationState[]
     */
    public function getMigrations(): array
    {
        $result = [];

        // process "pre" migrations
        $preEvent = new PreMigrationEvent($this->connection);
        $this->eventDispatcher->dispatch(MigrationEvents::PRE_UP, $preEvent);
        $preMigrations = $preEvent->getMigrations();
        foreach ($preMigrations as $migration) {
            $result[] = new MigrationState($migration);
        }
        $this->loadedVersions = $preEvent->getLoadedVersions();

        // process main migrations
        $migrationDirectories = $this->getMigrationDirectories();
        $this->filterMigrations($migrationDirectories);
        $this->createMigrationObjects(
            $result,
            $this->loadMigrationScripts($migrationDirectories)
        );

        $result[] = new MigrationState(new UpdateBundleVersionMigration($result, $this->migrationTable));

        // process "post" migrations
        $postEvent = new PostMigrationEvent($this->connection);
        $this->eventDispatcher->dispatch(MigrationEvents::POST_UP, $postEvent);
        $postMigrations = $postEvent->getMigrations();
        foreach ($postMigrations as $migration) {
            $result[] = new MigrationState($migration);
        }

        return $result;
    }

    /**
     * @return MigrationState[]
     */
    public function getPlainMigrations(): array
    {
        $result = [];

        // process "pre" migrations
        $preEvent = new PreMigrationEvent($this->connection);
        $this->eventDispatcher->dispatch(MigrationEvents::PRE_UP, $preEvent);
        $preMigrations = $preEvent->getMigrations();
        foreach ($preMigrations as $migration) {
            $result[] = new MigrationState($migration);
        }
        $this->loadedVersions = [];

        // process main migrations
        $migrationDirectories = $this->getMigrationDirectories();
        $this->filterMigrations($migrationDirectories);
        $this->createMigrationObjects(
            $result,
            $this->loadMigrationScripts($migrationDirectories)
        );

        $result[] = new MigrationState(new UpdateBundleVersionMigration($result, $this->migrationTable));

        // process "post" migrations
        $postEvent = new PostMigrationEvent($this->connection);
        $this->eventDispatcher->dispatch(MigrationEvents::POST_UP, $postEvent);
        $postMigrations = $postEvent->getMigrations();
        foreach ($postMigrations as $migration) {
            $result[] = new MigrationState($migration);
        }

        return $result;
    }

    /**
     * Return list of bundles/package names with migration path.
     *
     * @param string $migrationPrefix
     *
     * @return array bundle name = [dir_name, namespace]
     */
    public function getBundleList(string $migrationPrefix = ''): array
    {
        $bundles = $this->migrationsPaths;

        if (!empty($this->bundles)) {
            $includedBundles = [];
            foreach ($this->bundles as $bundleName) {
                if (isset($bundles[$bundleName])) {
                    $includedBundles[$bundleName] = $bundles[$bundleName];
                }
            }
            $bundles = $includedBundles;
        }

        if (!empty($this->excludeBundles)) {
            foreach ($this->excludeBundles as $excludeBundle) {
                unset($bundles[$excludeBundle]);
            }
        }

        foreach ($bundles as $name => $config) {
            $bundlePath = $config['dir_name'];
            $bundleMigrationPath = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                preg_replace('#(\/+|\\+)#', '/', $bundlePath . '/' . $migrationPrefix)
            );

            $bundles[$name]['dir_name'] = rtrim($bundleMigrationPath, DIRECTORY_SEPARATOR);
        }

        return $bundles;
    }

    /**
     * Gets a list of all directories contain migration scripts
     *
     * @return array
     *      key   = bundle name
     *      value = array
     *      .    key   = a migration version (actually it equals the name of migration directory)
     *      .            or empty string for root migration directory
     *      .    value = full path to a migration directory
     */
    protected function getMigrationDirectories(): array
    {
        $result = [];

        $migrations = $this->getBundleList($this->migrationPath);
        foreach ($migrations as $name => $configuration) {
            $bundleMigrationPath = $configuration['dir_name'];

            if (is_dir($bundleMigrationPath)) {
                $bundleMigrationDirectories = [];

                // get directories contain versioned migration scripts
                $finder = new Finder();
                $finder->directories()->depth(0)->in($bundleMigrationPath);
                /** @var SplFileInfo $directory */
                foreach ($finder as $directory) {
                    $bundleMigrationDirectories[$directory->getRelativePathname()] = $directory->getPathname();
                }
                // add root migration directory (it may contains an installation script)
                $bundleMigrationDirectories[''] = $bundleMigrationPath;
                // sort them by version number (the oldest version first)
                if (!empty($bundleMigrationDirectories)) {
                    uksort(
                        $bundleMigrationDirectories,
                        function ($a, $b) {
                            return version_compare($a, $b);
                        }
                    );
                }

                $result[$name] = $bundleMigrationDirectories;
            }
        }

        return $result;
    }

    /**
     * Finds migration files and call "include_once" for each file
     *
     * @param array $migrationDirectories
     *               key   = bundle name
     *               value = array
     *               .    key   = a migration version or empty string for root migration directory
     *               .    value = full path to a migration directory
     *
     * @return array loaded files
     *               'migrations' => array
     *               .      key   = full file path
     *               .      value = array
     *               .            'bundleName' => bundle name
     *               .            'version'    => migration version
     *               'installers' => array
     *               .      key   = full file path
     *               .      value = bundle name
     *               'bundles'    => string[] names of bundles
     */
    protected function loadMigrationScripts(array $migrationDirectories)
    {
        $migrations = [];
        $installers = [];

        foreach ($migrationDirectories as $bundleName => $bundleMigrationDirectories) {
            foreach ($bundleMigrationDirectories as $migrationVersion => $migrationPath) {
                $fileFinder = new Finder();
                $fileFinder->depth(0)->files()->name('*.php')->in($migrationPath);
                foreach ($fileFinder as $file) {
                    /** @var SplFileInfo $file */
                    $filePath = $file->getPathname();
                    include_once $filePath;
                    if (empty($migrationVersion)) {
                        $installers[$filePath] = $bundleName;
                    } else {
                        $migrations[$filePath] = ['bundleName' => $bundleName, 'version' => $migrationVersion];
                    }
                }
            }
        }

        return [
            'migrations' => $migrations,
            'installers' => $installers,
            'bundles'    => array_keys($migrationDirectories),
        ];
    }

    /**
     * Creates an instances of all classes implement migration scripts
     *
     * @param MigrationState[] $result
     * @param array            $files Files contain migration scripts
     *                                'migrations' => array
     *                                .      key   = full file path
     *                                .      value = array
     *                                .            'bundleName' => bundle name
     *                                .            'version'    => migration version
     *                                'installers' => array
     *                                .      key   = full file path
     *                                .      value = bundle name
     *                                'bundles'    => string[] names of bundles
     *
     * @throws \RuntimeException if a migration script contains more than one class
     */
    protected function createMigrationObjects(&$result, $files)
    {
        // load migration objects
        list($migrations, $installers) = $this->loadMigrationObjects($files);

        // remove versioned migrations covered by installers
        foreach ($installers as $installer) {
            $installerBundleName = $installer['bundleName'];
            $installerVersion    = $installer['version'];
            foreach ($files['migrations'] as $sourceFile => $migration) {
                if ($migration['bundleName'] === $installerBundleName
                    && version_compare($migration['version'], $installerVersion) < 1
                ) {
                    unset($migrations[$sourceFile]);
                }
            }
        }

        // group migration by bundle & version then sort them within same version
        $groupedMigrations = $this->groupAndSortMigrations($files, $migrations);

        // add migration objects to result tacking into account bundles order
        foreach ($files['bundles'] as $bundleName) {
            // add installers to the result
            foreach ($files['installers'] as $sourceFile => $installerBundleName) {
                if ($installerBundleName === $bundleName && isset($migrations[$sourceFile])) {
                    /** @var Installation $installer */
                    $installer = $migrations[$sourceFile];
                    $result[]  = new MigrationState(
                        $installer,
                        $installerBundleName,
                        $installer->getMigrationVersion()
                    );
                }
            }
            // add migrations to the result
            if (isset($groupedMigrations[$bundleName])) {
                foreach ($groupedMigrations[$bundleName] as $version => $versionedMigrations) {
                    foreach ($versionedMigrations as $migration) {
                        $result[]  = new MigrationState(
                            $migration,
                            $bundleName,
                            $version
                        );
                    }
                }
            }
        }
    }

    /**
     * Groups migrations by bundle and version
     * Sorts grouped migrations within the same version
     *
     * @param array $files
     * @param array $migrations
     *
     * @return array
     */
    protected function groupAndSortMigrations($files, $migrations)
    {
        $groupedMigrations = [];
        foreach ($files['migrations'] as $sourceFile => $migration) {
            if (isset($migrations[$sourceFile])) {
                $bundleName = $migration['bundleName'];
                $version    = $migration['version'];
                if (!isset($groupedMigrations[$bundleName])) {
                    $groupedMigrations[$bundleName] = [];
                }
                if (!isset($groupedMigrations[$bundleName][$version])) {
                    $groupedMigrations[$bundleName][$version] = [];
                }
                $groupedMigrations[$bundleName][$version][] = $migrations[$sourceFile];
            }
        }

        foreach ($groupedMigrations as $bundleName => $versions) {
            foreach ($versions as $version => $versionedMigrations) {
                if (count($versionedMigrations) > 1) {
                    usort(
                        $groupedMigrations[$bundleName][$version],
                        function ($a, $b) {
                            $aOrder = 0;
                            if ($a instanceof OrderedMigrationInterface) {
                                $aOrder = $a->getOrder();
                            }

                            $bOrder = 0;
                            if ($b instanceof OrderedMigrationInterface) {
                                $bOrder = $b->getOrder();
                            }

                            if ($aOrder === $bOrder) {
                                return 0;
                            } elseif ($aOrder < $bOrder) {
                                return -1;
                            } else {
                                return 1;
                            }
                        }
                    );
                }
            }
        }

        return $groupedMigrations;
    }


    /**
     * Loads migration objects
     *
     * @param $files
     *
     * @return array
     * @throws \RuntimeException
     */
    protected function loadMigrationObjects($files)
    {
        $migrations = [];
        $installers = [];
        $declared   = get_declared_classes();

        foreach ($declared as $className) {
            $reflClass  = new \ReflectionClass($className);
            $sourceFile = $reflClass->getFileName();
            if (isset($files['migrations'][$sourceFile])) {
                if (is_subclass_of($className, 'Okvpn\Bundle\MigrationBundle\Migration\Migration')) {
                    $migration = new $className;
                    if (isset($migrations[$sourceFile])) {
                        throw new \RuntimeException('A migration script must contains only one class.');
                    }
                    if ($migration instanceof ContainerAwareInterface) {
                        $migration->setContainer($this->container);
                    }
                    $migrations[$sourceFile] = $migration;
                }
            } elseif (isset($files['installers'][$sourceFile])) {
                if (is_subclass_of($className, 'Okvpn\Bundle\MigrationBundle\Migration\Installation')) {
                    /** @var \Okvpn\Bundle\MigrationBundle\Migration\Installation $installer */
                    $installer = new $className;
                    if (isset($migrations[$sourceFile])) {
                        throw new \RuntimeException('An installation  script must contains only one class.');
                    }
                    if ($installer instanceof ContainerAwareInterface) {
                        $installer->setContainer($this->container);
                    }
                    $migrations[$sourceFile] = $installer;
                    $installers[$sourceFile] = [
                        'bundleName' => $files['installers'][$sourceFile],
                        'version'    => $installer->getMigrationVersion(),
                    ];
                }
            }
        }

        return [
            $migrations,
            $installers
        ];
    }


    /**
     * Removes already installed migrations
     *
     * @param array $migrationDirectories
     *      key   = bundle name
     *      value = array
     *      .    key   = a migration version or empty string for root migration directory
     *      .    value = full path to a migration directory
     */
    protected function filterMigrations(array &$migrationDirectories)
    {
        if (!empty($this->loadedVersions)) {
            foreach ($migrationDirectories as $bundleName => $bundleMigrationDirectories) {
                $loadedVersion = isset($this->loadedVersions[$bundleName])
                    ? $this->loadedVersions[$bundleName]
                    : null;
                if ($loadedVersion) {
                    foreach (array_keys($bundleMigrationDirectories) as $migrationVersion) {
                        if (empty($migrationVersion) || version_compare($migrationVersion, $loadedVersion) < 1) {
                            unset($migrationDirectories[$bundleName][$migrationVersion]);
                        }
                    }
                }
            }
        }
    }
}
