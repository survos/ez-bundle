<?php

namespace Survos\SurvosEzBundle;

use Survos\SurvosEzBundle\Command\EzCommand;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosEzBundle extends AbstractBundle
{
	public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
	{
		$services = $container->services();

		        $services->set(EzCommand::class)
		            ->tag('console.command');
	}

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->arrayNode('entity_dirs')->default([
                'src/Entity',
            ])->info('The directories to scan for Ez* attributes')->end()
            ->end();
    }

}
