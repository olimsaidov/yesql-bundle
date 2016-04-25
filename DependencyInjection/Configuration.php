<?php

namespace Ox\YesqlBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('yesql');

        $rootNode
            ->children()
            ->scalarNode('connection')
            ->defaultValue('default')
            ->end()
            ->arrayNode('services')
            ->prototype('array')
            ->children()
            ->scalarNode('path')->end()
            ->scalarNode('name')->end()
            ->scalarNode('connection')->end()
            ->end()
            ->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
