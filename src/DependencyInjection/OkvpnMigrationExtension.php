<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MigrationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

final class OkvpnMigrationExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (isset($config['dir_prefix']) && null !== $config['dir_prefix']) {
            $container->setParameter('okvpn.migrations_path', $config['dir_prefix']);
        } elseif (isset($config['migrations_path']) && null !== $config['migrations_path']) {
            $container->setParameter('okvpn.migrations_path', $config['migrations_path']);
        }

        if (isset($config['table_name']) && null !== $config['table_name']) {
            $container->setParameter('okvpn.migrations_table', $config['table_name']);
        } elseif (isset($config['migrations_table']) && null !== $config['migrations_table']) {
            $container->setParameter('okvpn.migrations_table', $config['migrations_table']);
        }

        $migrationsPaths = [];
        foreach ($container->getParameter('kernel.bundles_metadata') as $bundleName => $bundle) {
            $migrationsPaths[$bundleName] = [
                'dir_name' => $bundle['path'],
                'namespace' => $bundle['namespace'],
            ];
        }

        // Add root_dir to migration paths
        if (empty($config['migrations_paths']) && Kernel::MAJOR_VERSION >= 4) {
            $config['migrations_paths']['App'] = [
                'dir_name' => $container->getParameter('kernel.root_dir'),
                'namespace' => 'App',
            ];
        }

        $container->getDefinition('okvpn_migration.migrations.loader')
            ->replaceArgument(6, array_merge($migrationsPaths, $config['migrations_paths'] ?? []));

        if (!class_exists('\Twig_Extension') && class_exists('Twig\Extension\AbstractExtension')) {
            $container->getDefinition('okvpn_migration.tools.schema_diff_dumper')
                ->setArgument(2, '@OkvpnMigration/schema-diff-template-v3.php.twig');
            $container->getDefinition('okvpn_migration.tools.schema_dumper')
                ->setArgument(2, '@OkvpnMigration/schema-template-v3.php.twig');
        }
    }
}
