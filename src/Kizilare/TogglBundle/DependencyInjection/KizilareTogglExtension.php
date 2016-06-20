<?php

namespace Kizilare\TogglBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class KizilareTogglExtension extends Extension
{
    const PARAMETER_HABITS = 1;
    const PARAMETER_PROJECTS = 2;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $definition = $container->getDefinition('kizilare.toggl.schedule_loader');
        $definition->addArgument($config['projects']);

        $definition = $container->getDefinition('kizilare.toggl.api');
        $definition->setArguments([$config['api_key'], $config['api_url'], $config['workspace_id']]);

        $container->setParameter('toggl.habits', $config['habits']);
    }
}
