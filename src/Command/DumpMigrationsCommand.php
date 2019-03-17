<?php

namespace Okvpn\Bundle\MigrationBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DumpMigrationsCommand extends ContainerAwareCommand
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
    protected $migrationPath;

    /**
     * @var string
     */
    protected $version;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('okvpn:migration:dump')
            ->addOption('plain-sql', null, InputOption::VALUE_NONE, 'Out schema as plain sql queries')
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_OPTIONAL,
                'Bundle name for which migration wll be generated'
            )
            ->addOption(
                'migration-version',
                null,
                InputOption::VALUE_OPTIONAL,
                'Migration version',
                'v1_0'
            )
            ->setDescription('Dump existing database structure.');
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('bundle')) {
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
        $this->version = $input->getOption('migration-version');
        $this->initializeBundleRestrictions($input->getOption('bundle'));
        $this->initializeMetadataInformation();
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var Schema $schema */
        $schema = $doctrine->getConnection()->getSchemaManager()->createSchema();

        if ($input->getOption('plain-sql')) {
            /** @var Connection $connection */
            $connection = $this->getContainer()->get('doctrine')->getConnection();
            $sqls = $schema->toSql($connection->getDatabasePlatform());
            foreach ($sqls as $sql) {
                $output->writeln($sql . ';');
            }
        } else {
            $this->dumpPhpSchema($schema, $output);
        }
    }

    /**
     * @param string $bundle
     */
    protected function initializeBundleRestrictions($bundle)
    {
        if ($bundle) {
            $bundles = $this->getContainer()->get('okvpn_migration.migrations.loader')->getBundleList();
            if (!array_key_exists($bundle, $bundles)) {
                throw new \InvalidArgumentException(
                    sprintf('Bundle "%s" is not a known bundle', $bundle)
                );
            }

            $this->namespace = $bundles[$bundle]['namespace'];
            $this->className = $bundle . 'Installer';
            $this->migrationPath = $bundles[$bundle]['dir_name'];
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
                    if (strpos($entityMetadata->getReflectionClass()->getFileName(), $this->migrationPath) === 0) {
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
     * @param Schema          $schema
     * @param OutputInterface $output
     */
    protected function dumpPhpSchema(Schema $schema, OutputInterface $output)
    {
        $visitor = $this->getContainer()->get('okvpn_migration.tools.schema_dumper');
        $schema->visit($visitor);

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
}
