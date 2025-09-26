<?php
declare(strict_types=1);

namespace Survos\EzBundle;

use ReflectionClass;
use Survos\EzBundle\Attribute\EzAdmin;
use Survos\EzBundle\Attribute\EzField;
use Survos\EzBundle\Attribute\Page;
use Survos\EzBundle\Service\EzService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SurvosEzBundle extends AbstractBundle
{

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        // Register EzService
        $container->register(EzService::class, EzService::class)
            ->setPublic(true);

        // Register MakeAdminCommand
        $container->register('survos_ez.command.make_admin', 'Survos\EzBundle\Command\MakeAdminCommand')
            ->addTag('console.command');

        // Set default entity_dirs parameter
        if (!$container->hasParameter('survos_ez.entity_dirs')) {
            $container->setParameter('survos_ez.entity_dirs', ['src/Entity']);
        }

        // Tiny embedded compiler pass to keep everything in one place.
        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $projectDir = (string) $container->getParameter('kernel.project_dir');
                /** @var list<string> $dirs */
                $dirs = (array) ($container->hasParameter('survos_ez.entity_dirs')
                    ? $container->getParameter('survos_ez.entity_dirs')
                    : ['src/Entity']);

                // Normalize dirs to absolute paths
                $absDirs = array_map(
                    fn (string $d) => str_starts_with($d, DIRECTORY_SEPARATOR) ? $d : $projectDir . DIRECTORY_SEPARATOR . $d,
                    $dirs
                );

                $classes = [];
                foreach ($absDirs as $dir) {
                    if (!is_dir($dir)) {
                        continue;
                    }
                    foreach ($this->phpFiles($dir) as $file) {
                        if ($fqcn = $this->classFromFile($file)) {
                            // Avoid triggering autoload for non-existent classes
                            if (class_exists($fqcn)) {
                                $classes[] = $fqcn;
                            }
                        }
                    }
                }

                $map = [];
                foreach ($classes as $class) {
                    $ref = new ReflectionClass($class);
                    if ($ref->isAbstract()) {
                        continue;
                    }

                    // Class-level attribute
                    /** @var EzAdmin|null $ezAdmin */
                    $ezAdmin = null;
                    foreach ($ref->getAttributes(EzAdmin::class) as $attr) {
                        $ezAdmin = $attr->newInstance();
                        break;
                    }
                    if (!$ezAdmin) {
                        // If an entity has only property-level EzField, we still include it when at least one property is annotated.
                        $hasAnyEzField = false;
                        foreach ($ref->getProperties() as $prop) {
                            if ($prop->getAttributes(EzField::class)) {
                                $hasAnyEzField = true;
                                break;
                            }
                        }
                        if (!$hasAnyEzField) {
                            continue; // nothing EZ-related here
                        }
                        $ezAdmin = new EzAdmin(); // derive defaults
                    }

                    // Derive defaults from class if not specified
                    $short = $ref->getShortName();
                    $adminData = [
                        'label'       => $ezAdmin->label ?? $this->humanize($short),
                        'icon'        => $ezAdmin->icon,
                        'crudController' => $ezAdmin->crudController,
                        'defaultSort' => $ezAdmin->defaultSort,
                        'indexMax'    => $ezAdmin->indexMax ?? 7, // replaces EA's hard-coded 7
                        'hiddenPages' => $this->validatePages($ezAdmin->hiddenPages),
                        'pageSizes'   => $ezAdmin->pageSizes,
                    ];

                    // Property-level attributes
                    $fields = [];
                    foreach ($ref->getProperties() as $prop) {
                        $propAttrs = $prop->getAttributes(EzField::class);
                        if (!$propAttrs) {
                            continue;
                        }
                        /** @var EzField $ef */
                        $ef = $propAttrs[0]->newInstance();

                        $fields[$prop->getName()] = [
                            'index'        => $ef->index,
                            'order'        => $ef->order,
                            'filter'       => $ef->filter,
                            'sortable'     => $ef->sortable,
                            'label'        => $ef->label,
                            'help'         => $ef->help,
                            'width'        => $ef->width,
                            'format'       => $ef->format,
                            'fieldClass'   => $ef->fieldClass,
                            'templatePath' => $ef->templatePath,
                            'type'         => $ef->type,
                            'showOn'       => $this->validatePages($ef->showOn),
                            'hideOn'       => $this->validatePages($ef->hideOn),
                            'filterType'   => $ef->filterType,
                            'filterOptions'=> $ef->filterOptions,
                        ];
                    }

                    // Only register if we have at least one field or an EzAdmin
                    $map[$class] = [
                        'admin'  => $adminData,
                        'fields' => $fields,
                    ];
                }

                // Inject map into EzService definition (creating if needed)
                if (!$container->hasDefinition(EzService::class)) {
                    $container->setDefinition(EzService::class, (new Definition(EzService::class))->setPublic(true));
                }
                $def = $container->getDefinition(EzService::class);
                $def->setArgument('$map', $map);
            }

            /** @return \Generator<string> */
            private function phpFiles(string $dir): \Generator
            {
                $it = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
                );
                /** @var \SplFileInfo $fi */
                foreach ($it as $fi) {
                    if ($fi->isFile() && $fi->getExtension() === 'php') {
                        yield $fi->getPathname();
                    }
                }
            }

            private function classFromFile(string $file): ?string
            {
                $src = @file_get_contents($file);
                if ($src === false) {
                    return null;
                }
                $ns = $cls = null;
                $tokens = token_get_all($src);
                $count = \count($tokens);
                for ($i = 0; $i < $count; $i++) {
                    if (\is_array($tokens[$i]) && $tokens[$i][0] === T_NAMESPACE) {
                        $ns = '';
                        for ($j = $i + 1; $j < $count; $j++) {
                            if ($tokens[$j] === ';') {
                                break;
                            }
                            if (\is_array($tokens[$j]) && \in_array($tokens[$j][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                                $ns .= $tokens[$j][1];
                            }
                        }
                    }
                    if (\is_array($tokens[$i]) && \in_array($tokens[$i][0], [T_CLASS, T_ENUM], true)) {
                        // skip "class" used in anonymous classes: look for T_STRING next
                        for ($j = $i + 1; $j < $count; $j++) {
                            if (\is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                                $cls = $tokens[$j][1];
                                break 2;
                            }
                        }
                    }
                }
                if (!$cls) {
                    return null;
                }
                return $ns ? $ns . '\\' . $cls : $cls;
            }

            private function humanize(string $short): string
            {
                return trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $short) ?? $short);
            }

            /** @param ?array $pages @return ?array */
            private function validatePages(?array $pages): ?array
            {
                if ($pages === null) {
                    return null;
                }
                $valid = [];
                $allowed = Page::all();
                foreach ($pages as $p) {
                    if (\in_array($p, $allowed, true)) {
                        $valid[] = $p;
                    }
                }
                return $valid ?: null;
            }
        });
    }
}
