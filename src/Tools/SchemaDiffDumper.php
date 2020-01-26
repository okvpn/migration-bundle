<?php

namespace Okvpn\Bundle\MigrationBundle\Tools;

use Doctrine\DBAL\Schema\SchemaDiff;
use Twig\Environment;

class SchemaDiffDumper
{
    const SCHEMA_TEMPLATE = '@OkvpnMigration/schema-diff-template.php.twig';
    const DEFAULT_CLASS_NAME = 'AllMigration';
    const DEFAULT_VERSION = 'v1_0';

    /**
     * @var SchemaDiff
     */
    protected $schemaDiff;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $migrationPath;

    /**
     * @var string
     */
    protected $schemaTemplate;

    /**
     * @param Environment $twig
     * @param string $migrationPath
     * @param string $schemaTemplate
     */
    public function __construct($twig, string $migrationPath, string $schemaTemplate = self::SCHEMA_TEMPLATE)
    {
        $this->twig = $twig;
        $this->migrationPath = $migrationPath;
        $this->schemaTemplate = $schemaTemplate;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSchemaDiff(SchemaDiff $schemaDiff)
    {
        $this->schemaDiff = $schemaDiff;
    }

    /**
     * @param array|null $allowedTables
     * @param string|null $namespace
     * @param string $className
     * @param string $version
     * @param array|null $extendedOptions
     * @return string
     */
    public function dump(
        array $allowedTables = null,
        $namespace = null,
        $className = self::DEFAULT_CLASS_NAME,
        $version = self::DEFAULT_VERSION,
        array $extendedOptions = null
    ) {
        if ($this->twig === null) {
            throw new \RuntimeException('Twig is required. You need install "symfony/twig-bundle" to use this command');
        }

        $migrationPath = trim(preg_replace('/\//', '\\', $this->migrationPath), "\\");
        return $this->twig->render(
            $this->schemaTemplate,
            [
                'schema' => $this->schemaDiff,
                'allowedTables' => $allowedTables,
                'namespace' => $this->getMigrationNamespace($namespace),
                'className' => $className,
                'version' => $version,
                'extendedOptions' => $extendedOptions,
                'migrationPath' => $migrationPath
            ]
        );
    }

    /**
     * @param string $namespace
     *
     * @return string
     */
    protected function getMigrationNamespace($namespace)
    {
        if ($namespace) {
            $namespace = str_replace('\\Entity', '', $namespace);
        }

        return $namespace;
    }
}
