<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Okvpn\Bundle\MigrationBundle\Provider\MigrationSchemaProvider;
use Okvpn\Bundle\MigrationBundle\Provider\OrmSchemaProvider;
use Okvpn\Bundle\MigrationBundle\Provider\SchemaProviderInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DiffMigrationsCommand extends ContainerAwareCommand
{
    protected const MIGRATION_CLASS_PREFIX = 'Migration';

    /**
     * @var array
     */
    protected $allowedTables = [];

    /**
     * @var array
     */
    protected $extendedFieldOptions = [];

    /**
     * @var array
     */
    protected $entities;

    /**
     * @var string
     */
    protected $migrationPath;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $version;

    /**
     * @var SchemaProviderInterface
     */
    protected $schemaProvider;

    /**
     * @var SchemaProviderInterface
     */
    protected $okvpnSchemaProvider;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('okvpn:migration:diff')
            ->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Out schema as plain sql queries')
            ->addOption('bundle', null, InputOption::VALUE_REQUIRED, 'Bundle name for which migration wll be generated')
            ->addOption('migration-version', null, InputOption::VALUE_OPTIONAL, 'Migration version, for example v1_0')
            ->addOption('write', null, InputOption::VALUE_NONE, 'Write migration to your filesystem')
            ->addOption('entity', null, InputOption::VALUE_OPTIONAL, 'Dump migration only for this entity, for example: \'App\\\\Bundle\\\\User*\', \'^App\\\\(.*)\\\\Region$\'')
            ->setDescription('Compare current existing database structure with orm structure');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('bundle') && !$input->getOption('entity')) {
            $helper = $this->getHelper('question');
            $question = new Question("<info>Please select your package/bundle name:</info>\n > ");
            $bundleNames = array_keys($this->getContainer()->get('okvpn_migration.migrations.loader')->getBundleList());
            $question->setAutocompleterValues($bundleNames);
            $question->setValidator(function ($answer) use ($bundleNames) {
                if (!in_array($answer, $bundleNames)) {
                    throw new \RuntimeException(sprintf('Package "%s" does not exist.', $answer));
                }
                return $answer;
            });

            $question->setMaxAttempts(3);
            $bundle = $helper->ask($input, $output, $question);
            $input->setOption('bundle', $bundle);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('bundle') && !$input->getOption('entity')) {
            throw new \InvalidArgumentException('The "bundle" or "entity" option can not be empty');
        }

        $this->initializeBundleRestrictions($input->getOption('bundle'));
        $this->initializeEntityRestrictions($input->getOption('entity'));
        $this->version = $input->getOption('migration-version') ?: $this->getNextMigrationVersion();

        $this->initializeMetadataInformation();
        $doctrine = $this->getContainer()->get('doctrine');
        $connection = $doctrine->getConnection();

        $okvpnSchema = $this->getOkvpnSchemaProvider()->createSchema();
        $ormSchema = $this->getSchemaProvider()->createSchema();
        $schemaDiff = Comparator::compareSchemas($okvpnSchema, $ormSchema);

        $this->removeExcludedTables($schemaDiff);

        if ($input->getOption('plain-sql')) {
            /** @var Connection $connection */
            $sqls = $schemaDiff->toSql($connection->getDatabasePlatform());
            foreach ($sqls as $sql) {
                $output->writeln($sql . ';');
            }
        } else {
            $this->dumpPhpSchema($schemaDiff, $output, $input->getOption('write'));
        }
    }

    /**
     * @return OrmSchemaProvider|SchemaProviderInterface
     */
    protected function getSchemaProvider()
    {
        if (!$this->schemaProvider) {
            $this->schemaProvider = new OrmSchemaProvider($this->getContainer()->get('doctrine')->getManager());
        }

        return $this->schemaProvider;
    }

    /**
     * @return MigrationSchemaProvider|SchemaProviderInterface
     */
    protected function getOkvpnSchemaProvider()
    {
        if (!$this->okvpnSchemaProvider) {
            $this->okvpnSchemaProvider = new MigrationSchemaProvider(
                $this->getContainer()->get('okvpn_migration.migrations.loader')
            );
        }

        return $this->okvpnSchemaProvider;
    }

    /**
     * @param string $bundle
     */
    protected function initializeBundleRestrictions(?string $bundle)
    {
        if ($bundle) {
            $bundles = $this->getContainer()->get('okvpn_migration.migrations.loader')->getBundleList();
            if (!array_key_exists($bundle, $bundles)) {
                throw new \InvalidArgumentException(
                    sprintf('Bundle "%s" is not a known bundle', $bundle)
                );
            }

            $this->migrationPath = $bundles[$bundle]['dir_name'];
            $this->className = $bundle;
            $this->namespace = $bundles[$bundle]['namespace'];
        }
    }

    /**
     * @param string $entity
     */
    protected function initializeEntityRestrictions(?string $entity)
    {
        if ($entity) {
            $doctrine = $this->getContainer()->get('doctrine');
            /** @var ClassMetadataInfo[] $entities */
            $entities = array_filter(
                $doctrine->getManager()->getMetadataFactory()->getAllMetadata(),
                function (ClassMetadataInfo $item) use ($entity) {
                    /** @var ClassMetadataInfo $item */
                    return preg_match('/' . str_replace('\\', '\\\\', $entity) . '/', $item->getName());
                }
            );

            if (!$entities) {
                throw new \InvalidArgumentException(sprintf('Entity "%s" is not a known.', $entity));
            }

            $packages = array_filter(
                $this->getContainer()->get('okvpn_migration.migrations.loader')->getBundleList(),
                function (array $package) use ($entities) {
                    foreach ($entities as $entity) {
                        if (strpos($entity->getReflectionClass()->getFileName(), $package['dir_name']) !== 0) {
                            return false;
                        }
                    }

                    return true;
                }
            );

            if (!$packages) {
                throw new \InvalidArgumentException(sprintf('Not found package for this entity "%s".', $entity));
            }

            $this->entities = array_map(function (ClassMetadataInfo $info) {
                return $info->getName();
            }, $entities);

            foreach ($packages as $packageName => $package) {
                $this->migrationPath = $package['dir_name'];
                $this->className = $packageName;
                $this->namespace = $package['namespace'];
            }
        }
    }

    /**
     * Process metadata information.
     */
    protected function initializeMetadataInformation()
    {
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var ClassMetadata[] $allMetadata */
        $allMetadata = $doctrine->getManager()->getMetadataFactory()->getAllMetadata();
        array_walk(
            $allMetadata,
            function (ClassMetadata $entityMetadata) {
                if ($this->migrationPath) {
                    if (strpos($entityMetadata->getReflectionClass()->getFileName(), $this->migrationPath) === 0) {
                        if ($this->entities && !in_array($entityMetadata->getName(), $this->entities)) {
                            return;
                        }

                        $this->allowedTables[$entityMetadata->getTableName()] = true;
                        foreach ($entityMetadata->getAssociationMappings() as $associationMappingInfo) {
                            if (!empty($associationMappingInfo['joinTable'])) {
                                $joinTableName = $associationMappingInfo['joinTable']['name'];
                                $this->allowedTables[$joinTableName] = true;
                            }
                        }
                    }
                }
            }
        );
    }

    /**
     * @param SchemaDiff $schema
     * @param OutputInterface $output
     * @param boolean $write
     */
    protected function dumpPhpSchema(SchemaDiff $schema, OutputInterface $output, $write = false)
    {
        $visitor = $this->getContainer()->get('okvpn_migration.tools.schema_diff_dumper');

        $visitor->acceptSchemaDiff($schema);
        $className = strpos($this->className, 'Bundle')
            ? $this->className : $this->className . self::MIGRATION_CLASS_PREFIX;

        $code = $visitor->dump(
            $this->allowedTables,
            $this->namespace,
            $className,
            $this->version,
            $this->extendedFieldOptions
        );

        if ($write === true) {
            $migrationPrefix = trim(preg_replace('/\//', '\\', $this->getContainer()->getParameter('okvpn.migrations_path')), "\\");
            $targetPath = $this->migrationPath . DIRECTORY_SEPARATOR
                . str_replace('\\', DIRECTORY_SEPARATOR, $migrationPrefix .'\\' . $this->version);
            if (!is_dir($targetPath)) {
                @mkdir($targetPath, 0777, true);
            }

            $output->writeln('<info> Using migration path ' . $targetPath . '</info>');
            $output->writeln('<info> Using version ' . $this->version . '</info>');
            $filename = $targetPath . DIRECTORY_SEPARATOR . $className . '.php';
            if (file_exists($filename)) {
                throw new \RuntimeException('Migration ' . $filename . ' is exists, try to specify migration-version manually');
            }

            file_put_contents($filename, $code);
            $output->writeln('<info> Write to file ' . $filename . '</info>');
        } else {
            $output->writeln($code);
        }
    }

    /**
     * @param SchemaDiff $schemaDiff
     */
    protected function removeExcludedTables(SchemaDiff $schemaDiff)
    {
        $excludes = ['okvpn_migrations'];

        /** @var Table $v */
        foreach ($schemaDiff->newTables as $k => $v) {
            if (in_array($v->getName(), $excludes) || !isset($this->allowedTables[$v->getName()])) {
                unset($schemaDiff->newTables[$k]);
            }
        }

        /** @var TableDiff $v */
        foreach ($schemaDiff->changedTables as $k => $v) {
            if (in_array($v->name, $excludes) || !isset($this->allowedTables[$v->name])) {
                unset($schemaDiff->changedTables[$k]);
            }
        }
    }

    protected function getNextMigrationVersion()
    {
        $migrations = $this->getContainer()->get('okvpn_migration.migrations.loader')->getPlainMigrations();
        $version = null;
        foreach ($migrations as $migration) {
            if (!$migration->getVersion() || $migration->getBundleName() !== $this->className) {
                continue;
            }

            if (null !== $version) {
                if (version_compare($version, $migration->getVersion()) === -1) {
                    $version = $migration->getVersion();
                }
            } else {
                $version = $migration->getVersion();
            }
        }

        if ($version === null) {
            return 'v1_0';
        }

        if (preg_match('/(\d+)$/', $version, $match)) {
            $next = $match[1] + 1;
            return preg_replace('/\d+$/', $next, $version);
        }

        return $version;
    }
}
