<?php

namespace Kizilare\TogglBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('kizilare_toggl');

        $rootNode->children()
            ->scalarNode('api_url')->isRequired()->end()
            ->scalarNode('api_key')->isRequired()->end()
            ->scalarNode('workspace_id')->isRequired()->end()
            ->arrayNode('habits')
                ->useAttributeAsKey('time')
                ->prototype('scalar')->end()
            ->end()
            ->arrayNode('projects')
                ->useAttributeAsKey('project')
                ->prototype('scalar')->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
