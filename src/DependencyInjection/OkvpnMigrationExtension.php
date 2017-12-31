<?php

namespace Okvpn\Bundle\MigrationBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class OkvpnMigrationExtension extends Extension
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

        if (isset($config['migrations_path']) && null !== $config['migrations_path']) {
            $container->setParameter('okvpn.migrations_path', $config['migrations_path']);
        }

        if (isset($config['migrations_table']) && null !== $config['migrations_table']) {
            $container->setParameter('okvpn.migrations_table', $config['migrations_table']);
        }
    }
}
