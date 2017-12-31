<?php

namespace Okvpn\Bundle\MigrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('okvpn_migration');

        $rootNode
            ->children()
                ->scalarNode('migrations_path')->end()
                ->scalarNode('migrations_table')->end()
            ->end();
        return $treeBuilder;
    }
}
