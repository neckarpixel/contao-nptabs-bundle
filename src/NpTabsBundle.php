<?php

declare(strict_types=1);

namespace Neckarpixel\NpTabsBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Konfiguration (config/config.yaml):
 *
 *     np_tabs:
 *         load_default_css: false   # Standard-CSS nicht laden (Theme liefert alles selbst)
 */
class NpTabsBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('load_default_css')
                    ->defaultTrue()
                    ->info('Lädt bundles/nptabs/nptabs.css, sobald ein Tabs-Element auf der Seite liegt.')
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()->set('np_tabs.load_default_css', $config['load_default_css']);

        $container->import('../config/services.yaml');
    }
}
