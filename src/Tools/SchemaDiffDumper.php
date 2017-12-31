<?php

namespace Okvpn\Bundle\MigrationBundle\Tools;

use Doctrine\DBAL\Schema\SchemaDiff;

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
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $migrationPath;

    /**
     * @param \Twig_Environment $twig
     * @param string $migrationPath
     */
    public function __construct(\Twig_Environment $twig, string $migrationPath)
    {
        $this->twig = $twig;
        $this->migrationPath = $migrationPath;
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
        $migrationPath = trim(preg_replace('/\//', '\\', $this->migrationPath), "\\");
        $content = $this->twig->render(
            self::SCHEMA_TEMPLATE,
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

        return $content;
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
