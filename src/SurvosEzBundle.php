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
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SurvosEzBundle extends AbstractBundle implements CompilerPassInterface
{
//    use HasAssetMapperTrait;

    protected string $extensionAlias = 'survos_ez';

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $builder->autowire(EzService::class)
            ->setArgument('$config', $config)
            ->setAutowired(true)
            ->setPublic(true)
            ->setAutoconfigured(true);


        foreach ([MakeAdminCommand::class, EzCommand::class,
                 ] as $class) {
            $builder->autowire($class)
                ->setPublic(true)
                ->setAutoconfigured(true)
                ->addTag('console.command');
        }

    }

    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();

        $rootNode
            ->children()
//            ->arrayNode('entity_dirs')->defaultValue(['src/Entity'])->end()
                ->booleanNode('enabled')->defaultTrue()->end()
            ->end();
    }


    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass($this);
    }

    /**
     * CompilerPass logic: Find all entities with #[EzIndex] and inject them into EzService
     */
    public function process(ContainerBuilder $container): void
    {
        $attributeClass = EzAdmin::class;
        $entityDir = $container->getParameter('kernel.project_dir') . '/src/Entity';
        $indexedClasses = [];
        $map = [];
        foreach ($this->getClassesInDirectory($entityDir) as $class) {
            assert(class_exists($class), "Missing $class in $entityDir");
            $ref = new ReflectionClass($class);
            if ($attrs = $ref->getAttributes($attributeClass)) {
                $attr = $attrs[0];
                $instance = $attr->newInstance();
                $indexedClasses[] = $class;
            }
//            $instance = $ref->newInstance();

            $fields = [];
            foreach ($ref->getProperties() as $prop) {
                $attrs = $prop->getAttributes(EzField::class);
                if (!$attrs) continue;
                /** @var EzField $ef */
                $ef = $attrs[0]->newInstance();

                $fields[$prop->getName()] = (array)$ef;
            }

            $map[$class] = ['fields' => $fields];
//        dd($indexedClasses);

            $container->setParameter('ez.indexed_entities', $indexedClasses);

        }
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
                $classes[] = ($class = $nsMatch[1] . '\\' . $classMatch[1]);
                assert(class_exists($class), "missing class $class in " . $contents);
            }
        }

        return $classes;
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Register Twig namespace for this bundle
        $builder->prependExtensionConfig('twig', [
            'paths' => [
                dirname(__DIR__) . '/templates' => 'SurvosEz',
                // Or if templates are in Resources/views:
                // dirname(__DIR__) . '/Resources/views' => 'SurvosEz',
            ]
        ]);
    }

}
