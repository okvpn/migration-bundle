<?php

declare(strict_types=1);

namespace Okvpn\Bundle\MigrationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
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
                ->scalarNode('migrations_path')
                    ->info('Deprecated use "dir_prefix"')
                ->end()
                ->scalarNode('migrations_table')
                    ->info('Deprecated use "dir_prefix"')
                ->end()
                ->scalarNode('table_name')
                    ->info('By default "okvpn_migrations"')
                ->end()
                ->scalarNode('dir_prefix')
                    ->info('By default "Migrations/Schema"')
                ->end()
                ->arrayNode('migrations_paths')
                    ->info('Lookup migrations directories')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('dir_name')->defaultNull()->end()
                            ->scalarNode('namespace')
                                ->info('Namespace is arbitrary but should be different from App\Migrations\Schema as migrations classes should NOT be autoloaded')
                                ->defaultNull()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
