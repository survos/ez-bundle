<?php

namespace Survos\EzBundle;

use ReflectionClass;
use Survos\EzBundle\Attribute\EzAdmin;
use Survos\EzBundle\Attribute\EzField;
use Survos\EzBundle\Command\EzCommand;
use Survos\EzBundle\Command\MakeAdminCommand;
use Survos\EzBundle\Service\EzService;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosEzBundle extends AbstractBundle implements CompilerPassInterface
{
    protected string $extensionAlias = 'survos_ez';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->autowire(EzService::class)
            ->setArgument('$config', $config)
            ->setAutowired(true)
            ->setPublic(true)
            ->setAutoconfigured(true);

        foreach ([MakeAdminCommand::class, EzCommand::class] as $class) {
            $builder->autowire($class)
                ->setPublic(true)
                ->setAutoconfigured(true)
                ->addTag('console.command');
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $rootNode = $definition->rootNode();

        $rootNode
            ->children()
                ->booleanNode('enabled')->defaultTrue()->end()
            ->end();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass($this);
    }

    public function process(ContainerBuilder $container): void
    {
        $entityDir = $container->getParameter('kernel.project_dir') . '/src/Entity';

        $indexedClasses = [];
        $map = [];

        foreach ($this->getClassesInDirectory($entityDir) as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $ref = new ReflectionClass($class);

            // Admin metadata
            $admin = [];
            if ($attrs = $ref->getAttributes(EzAdmin::class)) {
                /** @var EzAdmin $instance */
                $instance = $attrs[0]->newInstance();
                $admin = (array)$instance;
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
                $fields[$prop->getName()] = (array)$ef;
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
        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($rii as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if (str_ends_with($file->getBasename('.' . $file->getExtension()), 'Interface')) {
                continue;
            }

            $contents = file_get_contents($file->getRealPath());
            if (preg_match('/namespace\s+([^;]+);/i', $contents, $nsMatch)
                && preg_match('/^class\s+([A-Za-z_][A-Za-z0-9_]*)/m', $contents, $classMatch)) {
                $classes[] = $nsMatch[1] . '\\' . $classMatch[1];
            }
        }

        return $classes;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig', [
            'paths' => [
                dirname(__DIR__) . '/templates' => 'SurvosEz',
            ],
        ]);
    }
}
