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
     * @param Environment $twig
     * @param string $migrationPath
     */
    public function __construct($twig, string $migrationPath)
    {
        $this->twig = $twig;
        $this->migrationPath = $migrationPath;
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
        $content = $this->twig->render(
            self::SCHEMA_TEMPLATE,
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

        return $content;
    }

    protected function getMigrationNamespace($namespace)
    {
        if ($namespace) {
            $namespace = str_replace('\\Entity', '', $namespace);
        }

        return $namespace;
    }
}
