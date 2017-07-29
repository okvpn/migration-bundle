<?php

namespace Okvpn\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\ORM\Mapping\ClassMetadata;

use Okvpn\Bundle\MigrationBundle\Provider\MigrationSchemaProvider;
use Okvpn\Bundle\MigrationBundle\Provider\OrmSchemaProvider;
use Okvpn\Bundle\MigrationBundle\Provider\SchemaProviderInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DiffMigrationsCommand extends ContainerAwareCommand
{
    /**
     * @var array
     */
    protected $allowedTables = [];

    /**
     * @var array
     */
    protected $extendedFieldOptions = [];

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

    /** @var SchemaProviderInterface */
    protected $schemaProvider;

    /** @var SchemaProviderInterface */
    protected $okvpnSchemaProvider;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('okvpn:migration:diff')
            ->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Out schema as plain sql queries')
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_REQUIRED,
                'Bundle name for which migration wll be generated'
            )
            ->addOption(
                'migration-version',
                null,
                InputOption::VALUE_OPTIONAL,
                'Migration version',
                'v1_0'
            )
            ->setDescription('Diff');
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('bundle')) {
            throw new \Exception('Wrong bundle');
        }

        $this->version = $input->getOption('migration-version');
        $this->initializeBundleRestrictions($input->getOption('bundle'));
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
            $this->dumpPhpSchema($schemaDiff, $output);
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
    protected function initializeBundleRestrictions($bundle)
    {
        if ($bundle) {
            $bundles = $this->getContainer()->getParameter('kernel.bundles');
            if (!array_key_exists($bundle, $bundles)) {
                throw new \InvalidArgumentException(
                    sprintf('Bundle "%s" is not a known bundle', $bundle)
                );
            }
            $this->namespace = str_replace($bundle, 'Entity', $bundles[$bundle]);
            $this->className = $bundle . 'Installer';
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
                if ($this->namespace) {
                    if ($entityMetadata->namespace == $this->namespace) {
                        $this->allowedTables[$entityMetadata->getTableName()] = true;
                        foreach ($entityMetadata->getAssociationMappings() as $associationMappingInfo) {
                            if (!empty($associationMappingInfo['joinTable'])) {
                                $joinTableName = $associationMappingInfo['joinTable']['name'];
                                $this->allowedTables[$joinTableName] = true;
                            }
                        }
                        //$this->initializeExtendedFieldsOptions($entityMetadata);
                    }
                } else {
                    //$this->initializeExtendedFieldsOptions($entityMetadata);
                }
            }
        );
    }

    /**
     * @param SchemaDiff $schema
     * @param OutputInterface $output
     */
    protected function dumpPhpSchema(SchemaDiff $schema, OutputInterface $output)
    {
        $visitor = $this->getContainer()->get('okvpn_migration.tools.schema_diff_dumper');

        $visitor->acceptSchemaDiff($schema);

        $output->writeln(
            $visitor->dump(
                $this->allowedTables,
                $this->namespace,
                $this->className,
                $this->version,
                $this->extendedFieldOptions
            )
        );
    }

    /**
     * @param SchemaDiff $schemaDiff
     */
    private function removeExcludedTables(SchemaDiff $schemaDiff)
    {
        $excludes = [
            'okvpn_migrations',
        ];

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
}
