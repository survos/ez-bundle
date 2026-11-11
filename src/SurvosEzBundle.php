<?php

declare(strict_types=1);

namespace Survos\EzBundle;

use Survos\EzBundle\Attribute\EzAdmin;
use Survos\EzBundle\Attribute\EzField;
use Survos\EzBundle\Menu\ContextNavigation;
use Survos\EzBundle\Menu\EasyAdminMenus;
use Survos\EzBundle\Menu\LocaleMenuListener;
use Survos\EzBundle\Menu\MenuContextListener;
use Survos\EzBundle\Service\EzService;
use Survos\EzBundle\Twig\EzMenuExtension;
use Survos\Kit\AbstractUxBundle;
use Survos\Kit\SurvosKitBundle;
use Survos\MenuBundle\SurvosMenuBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Kernel\RequiredBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Finder\Finder;

#[RequiredBundle(\Survos\FieldBundle\SurvosFieldBundle::class)]
#[RequiredBundle(SurvosKitBundle::class)]
#[RequiredBundle(SurvosMenuBundle::class)]
// Symfony\Component\HttpKernel\Bundle\Bundle <-- Flex auto-registration marker (see Survos\Kit\AbstractSurvosBundle)
final class SurvosEzBundle extends AbstractUxBundle
{
    protected string $extensionAlias = 'survos_ez';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::loadExtension($config, $container, $builder);

        $builder->autowire(EzService::class)
            ->setArgument('$config', $config)
            ->setAutowired(true)
            ->setPublic(true)
            ->setAutoconfigured(true);

        foreach ([Service\CrudFieldReader::class, EasyAdminMenus::class, ContextNavigation::class, LocaleMenuListener::class, MenuContextListener::class, EzMenuExtension::class] as $class) {
            $builder->autowire($class)->setAutoconfigured(true);
        }
        $builder->getDefinition(EasyAdminMenus::class)->setArgument('$localePlacement', $config['localePlacement']);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();

        $rootNode
            ->children()
                ->enumNode('localePlacement')->values(['header', 'settings'])->defaultValue('header')->end()
            ->end();
    }

    public function process(ContainerBuilder $container): void
    {
        $entityDir = $container->getParameter('kernel.project_dir').'/src/Entity';

        $indexedClasses = [];
        $map = [];

        foreach ($this->getClassesInDirectory($entityDir) as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $ref = new \ReflectionClass($class);

            // Admin metadata
            $admin = [];
            if ($attrs = $ref->getAttributes(EzAdmin::class)) {
                /** @var EzAdmin $instance */
                $instance = $attrs[0]->newInstance();
                $admin = (array) $instance;
                $indexedClasses[] = $class;
            }

            // Field metadata
            $fields = [];
            foreach ($ref->getProperties() as $prop) {
                $attrs = $prop->getAttributes(EzField::class);
                if (!$attrs) {
                    continue;
                }
                /** @var EzField $ef */
                $ef = $attrs[0]->newInstance();
                $fields[$prop->getName()] = (array) $ef;
            }

            if ($admin || $fields) {
                $map[$class] = [
                    'admin' => $admin,
                    'fields' => $fields,
                ];
            }
        }

        $container->setParameter('ez.indexed_entities', $indexedClasses);

        if ($container->hasDefinition(EzService::class)) {
            $def = $container->getDefinition(EzService::class);
            $def->setArgument('$map', $map);
        }
    }

    private function getClassesInDirectory(string $dir): array
    {
        $classes = [];
        if (!is_dir($dir)) {
            return $classes;
        }
        foreach ((new Finder())->files()->in($dir)->name('*.php') as $file) {
            $relative = substr($file->getRelativePathname(), 0, -4);
            $class = 'App\\Entity\\'.str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
            if (class_exists($class)) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::prependExtension($container, $builder);
        $builder->prependExtensionConfig('twig', [
            'paths' => [dirname(__DIR__).'/templates/easyadmin' => 'EasyAdmin'],
        ]);
    }
}
