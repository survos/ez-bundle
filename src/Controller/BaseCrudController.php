<?php
declare(strict_types=1);

namespace Survos\EzBundle\Controller;

use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Survos\EzBundle\Attribute\Page;
use Survos\EzBundle\Service\EzService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Extend this in your app CRUDs.
 *
 * By default, fields & filters are derived from #[EzAdmin] and #[EzField] attributes,
 * falling back gracefully to EasyAdmin defaults if no attributes are present.
 */
abstract class BaseCrudController extends AbstractCrudController
{
    public function __construct(
        #[Autowire(service: EzService::class)]
        private readonly EzService $ez,
    ) {}

    public function configureActions(Actions $actions): Actions
    {
        // Reasonable defaults; tweak in child classes if needed
        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_ADMIN');
    }

    public function configureCrud(Crud $crud): Crud
    {
        $class = static::getEntityFqcn();
        $admin = $this->ez->getAdmin($class);

        // Derive label/icon/default sort from EzAdmin (if present)
        if (!empty($admin['label'])) {
            $crud = $crud
                ->setEntityLabelInSingular($admin['label'])
                ->setEntityLabelInPlural($admin['label'] . 's');
        }

        if (!empty($admin['defaultSort']) && \is_array($admin['defaultSort'])) {
            $crud = $crud->setDefaultSort($admin['defaultSort']);
        }

        if (!empty($admin['pageSizes']) && \is_array($admin['pageSizes'])) {
            $crud = $crud->setPaginatorPageSize($admin['pageSizes'][0] ?? 25)
                         ->setPaginatorPageSizeOptions($admin['pageSizes']);
        }

        // Respect hidden pages if configured
        $hidden = $admin['hiddenPages'] ?? null;
        if ($hidden) {
            if (\in_array(Page::NEW, $hidden, true)) {
                $crud = $crud->showEntityActionsInlined(); // keeps UI tidy when NEW is gone
            }
        }

        return $crud->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        $class  = static::getEntityFqcn();
        $map    = $this->ez->getFields($class);
        $entity = $this->getContext()?->getEntity();

        if (!$map || !$entity instanceof EntityDto) {
            return $filters; // no attributes → let EA defaults/child overrides handle it
        }

        foreach ($map as $name => $cfg) {
            if (!($cfg['filter'] ?? false)) {
                continue;
            }
            $type = $this->getDoctrineType($entity, $name);

            // Heuristic mapping Doctrine type → EA Filter type
            switch ($type) {
                case 'boolean':
                    $filters = $filters->add(BooleanFilter::new($name));
                    break;
                case 'datetime':
                case 'datetimetz':
                case 'date':
                case 'time':
                    $filters = $filters->add(DateTimeFilter::new($name));
                    break;
                case 'integer':
                case 'bigint':
                case 'smallint':
                case 'decimal':
                case 'float':
                case 'numeric':
                    $filters = $filters->add(NumericFilter::new($name));
                    break;
                default:
                    // associations?
                    if ($this->isAssociation($entity, $name)) {
                        $filters = $filters->add(EntityFilter::new($name));
                    } else {
                        $filters = $filters->add(TextFilter::new($name));
                    }
            }

            // Optional: handle explicit filterType/options from attribute
            if (!empty($cfg['filterType'])) {
                // You can extend here to honor 'choice', custom options, etc.
                if ($cfg['filterType'] === 'choice' && !empty($cfg['filterOptions']['choices'])) {
                    $filters = $filters->add(
                        ChoiceFilter::new($name)->setChoices($cfg['filterOptions']['choices'])
                    );
                }
            }
        }

        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        $class   = static::getEntityFqcn();
        $fieldsC = $this->ez->getFields($class);

        if (!$fieldsC) {
            // No EzField attributes → fallback to EA's default resolution (parent)
            yield from parent::configureFields($pageName);
            return;
        }

        $entity = $this->getContext()?->getEntity();
        $indexNames = $pageName === Crud::PAGE_INDEX
            ? $this->ez->getIndexFieldNames($class, 7)
            : $this->orderedNamesForPage($fieldsC, $pageName);

        // If no explicit index/showOn rules, fall back to parent defaults
        if (!$indexNames) {
            yield from parent::configureFields($pageName);
            return;
        }

        foreach ($indexNames as $name) {
            $cfg = $fieldsC[$name] ?? [];
            $field = $this->instantiateField($entity, $name, $cfg);

            if (!$field) {
                continue;
            }

            // Apply common presentation hints from attribute
            if (!empty($cfg['label'])) {
                $field = $field->setLabel($cfg['label']);
            }
            if (!empty($cfg['help'])) {
                $field = $field->setHelp($cfg['help']);
            }
            if (!empty($cfg['templatePath'])) {
                $field = $field->setTemplatePath($cfg['templatePath']);
            }
            if (\array_key_exists('sortable', $cfg) && $cfg['sortable'] !== null) {
                $field = $field->setSortable((bool)$cfg['sortable']);
            }

            yield $field;
        }
    }

    /**
     * Build an ordered list of field names for a given page using EzField::order + showOn/hideOn.
     * @return string[]
     */
    private function orderedNamesForPage(array $fieldsCfg, string $pageName): array
    {
        $wanted = [];
        foreach ($fieldsCfg as $name => $cfg) {
            // showOn/hideOn take precedence; else allow everything
            $showOn = $cfg['showOn'] ?? null;
            $hideOn = $cfg['hideOn'] ?? null;

            if ($hideOn && \in_array($this->pageToKey($pageName), $hideOn, true)) {
                continue;
            }
            if ($showOn && !\in_array($this->pageToKey($pageName), $showOn, true)) {
                continue;
            }

            // For index, require 'index: true' to avoid flooding
            if ($pageName === Crud::PAGE_INDEX && !($cfg['index'] ?? false)) {
                continue;
            }

            $wanted[$name] = $cfg['order'] ?? 1000;
        }

        asort($wanted, \SORT_NUMERIC);
        return array_keys($wanted);
    }

    private function pageToKey(string $eaPage): string
    {
        return match ($eaPage) {
            Crud::PAGE_INDEX  => Page::INDEX,
            Crud::PAGE_DETAIL => Page::DETAIL,
            Crud::PAGE_NEW    => Page::NEW,
            Crud::PAGE_EDIT   => Page::EDIT,
            default           => $eaPage,
        };
    }

    private function instantiateField(?EntityDto $entity, string $name, array $cfg)
    {
        // Respect explicit fieldClass from attribute if provided
        if (!empty($cfg['fieldClass']) && class_exists($cfg['fieldClass'])) {
            /** @var class-string $cls */
            $cls = $cfg['fieldClass'];
            // Most EA fields expose a static ::new($propertyName, $label = null)
            if (method_exists($cls, 'new')) {
                return $cls::new($name);
            }
        }

        // Otherwise pick a sensible default from Doctrine type
        $type = $this->getDoctrineType($entity, $name);

        return match ($type) {
            'integer', 'bigint', 'smallint' => IntegerField::new($name),
            'float', 'decimal', 'numeric'   => NumberField::new($name),
            'boolean'                       => BooleanField::new($name),
            'datetime', 'datetimetz', 'date','time' => DateTimeField::new($name),
            default => $this->isAssociation($entity, $name)
                ? AssociationField::new($name)
                : TextField::new($name),
        };
    }

    private function getDoctrineType(?EntityDto $entity, string $property): ?string
    {
        if (!$entity) {
            return null;
        }
        $meta = $entity->getPropertyMetadata($property);
        return $meta?->get('type');
    }

    private function isAssociation(?EntityDto $entity, string $property): bool
    {
        if (!$entity) {
            return false;
        }
        $meta = $entity->getPropertyMetadata($property);
        return (bool) $meta?->get('isAssociation');
    }
}
