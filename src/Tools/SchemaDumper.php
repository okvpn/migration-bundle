<?php

namespace Okvpn\Bundle\MigrationBundle\Tools;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Visitor\AbstractVisitor;
use Twig\Environment;

class SchemaDumper extends AbstractVisitor
{
    const SCHEMA_TEMPLATE = '@OkvpnMigration/schema-template.php.twig';
    const DEFAULT_CLASS_NAME = 'AllMigration';
    const DEFAULT_VERSION = 'v1_0';

    /**
     * @var Schema
     */
    protected $schema;

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
    protected $templateName;

    /**
     * @param Environment $twig
     * @param string $migrationPath
     * @param string $templateName
     */
    public function __construct($twig, string $migrationPath, string $templateName = self::SCHEMA_TEMPLATE)
    {
        $this->twig = $twig;
        $this->migrationPath = $migrationPath;
        $this->templateName = $templateName;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->schema = $schema;
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
            $this->templateName,
            [
                'schema' => $this->schema,
                'allowedTables' => $allowedTables,
                'namespace' => $this->getMigrationNamespace($namespace),
                'className' => $className,
                'version' => $version,
                'extendedOptions' => $extendedOptions,
                'migrationPath' => $migrationPath,
            ]
        );
    }

    protected function getMigrationNamespace($namespace)
    {
        if ($namespace) {
            $namespace = str_replace('\\Entity', '', $namespace);
        }

        return $namespace;
    }
}
