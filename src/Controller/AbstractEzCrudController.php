<?php
declare(strict_types=1);

namespace Survos\EzBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Survos\EzBundle\Attribute\Page;
use Survos\EzBundle\Field\LinkedTextField;
use Survos\EzBundle\Service\EzService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Extend this in your app CRUDs.
 *
 * Precedence for fields:
 *   1) preferredFields() from the CRUD controller
 *   2) EzField-driven fields
 *   3) EasyAdmin parent defaults (fill)
 *
 * Default security/UX:
 *   - Read-only (hide NEW/EDIT/DELETE) unless user has ROLE_ADMIN or ROLE_EDITOR,
 *     overridable per-entity via EzAdmin(editRoles: [...]).
 */
abstract class AbstractEzCrudController extends AbstractCrudController
{
    private EzService $ez;
    private Security $security;
    private ManagerRegistry $doctrine;

    #[Required]
    public function setEz(EzService $ez): void
    {
        $this->ez = $ez;
    }

    #[Required]
    public function setSecurity(Security $security): void
    {
        $this->security = $security;
    }

    #[Required]
    public function setDoctrine(ManagerRegistry $doctrine): void
    {
        $this->doctrine = $doctrine;
    }

    protected function ez(): EzService
    {
        return $this->ez;
    }

    protected function security(): Security
    {
        return $this->security;
    }

    protected function doctrine(): ManagerRegistry
    {
        return $this->doctrine;
    }

    /**
     * Override in child CRUDs for hard-coded “important first” fields.
     */
    protected function preferredFields(string $pageName): iterable
    {
        return [];
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions = parent::configureActions($actions);

        // this is on by default
//        $actions
//            ->add(Crud::PAGE_INDEX, Action::DETAIL);
//        return $actions;
        if ($this->isReadOnly()) {
            // Hide mutation actions entirely (clean read-only UX)
            return $actions->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE);
        }

        // Editable: still permission (defense in depth)
        $perm = $this->editRoleExpression();

        return $actions
            ->setPermission(Action::NEW, $perm)
            ->setPermission(Action::EDIT, $perm)
            ->setPermission(Action::DELETE, $perm)
            ->setPermission(Action::BATCH_DELETE, $perm);
    }

    public function configureCrud(Crud $crud): Crud
    {
        $class = static::getEntityFqcn();
        $admin = $this->ez()->getAdmin($class);

        if (!empty($admin['label'])) {
            $crud = $crud
                ->setEntityLabelInSingular($admin['label'])
                ->setEntityLabelInPlural($admin['label'] . 's');
        }

        if (!empty($admin['defaultSort']) && \is_array($admin['defaultSort'])) {
            $crud = $crud->setDefaultSort($admin['defaultSort']);
        }

        if (!empty($admin['pageSizes']) && \is_array($admin['pageSizes'])) {
            $crud = $crud->setPaginatorPageSize($admin['pageSizes'][0] ?? 25);
        }

        return $crud->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        $class = static::getEntityFqcn();
        $map   = $this->ez()->getFields($class);

        if (!$map) {
            return $filters;
        }

        $em = $this->doctrine()->getManagerForClass($class);
        $metadata = $em?->getClassMetadata($class);

        if (!$metadata) {
            return $filters;
        }

        foreach ($map as $name => $cfg) {
            if (!($cfg['filter'] ?? false)) {
                continue;
            }

            $type = null;
            if ($metadata->hasField($name)) {
                $type = $metadata->getTypeOfField($name);
            } elseif ($metadata->hasAssociation($name)) {
                $type = $metadata->isSingleValuedAssociation($name) ? 'association' : 'collection';
            }

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
                    $filters = $filters->add(
                        \in_array($type, ['association','collection'], true)
                            ? EntityFilter::new($name)
                            : TextFilter::new($name)
                    );
            }

            if (!empty($cfg['filterType'])) {
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
        $class    = static::getEntityFqcn();
        $fieldsC  = $this->ez()->getFields($class);
        $admin    = $this->ez()->getAdmin($class);
        $indexMax = (int)($admin['indexMax'] ?? 7);

        $seen = [];

        // 1) preferred (CRUD override)
        foreach ($this->preferredFields($pageName) as $field) {
            if ($field instanceof FieldInterface) {
                if ($field = $this->once($seen, $field)) {
                    yield $this->applyReadOnlyIfNeeded($field, $pageName);
                }
            }
        }

        // 2) EzField-driven
        if ($fieldsC) {
            $names = $pageName === Crud::PAGE_INDEX
                ? $this->ez()->getIndexFieldNames($class, $indexMax)
                : $this->orderedNamesForPage($fieldsC, $pageName);

            foreach ($names as $name) {
                $cfg = $fieldsC[$name] ?? [];
                $field = $this->buildFieldFromConfig($class, $name, $cfg);
                if (!$field) {
                    continue;
                }
                if ($field = $this->once($seen, $field)) {
                    yield $this->applyReadOnlyIfNeeded($field, $pageName);
                }
            }
        }

        // 3) parent fill (deduped)
        foreach (parent::configureFields($pageName) as $field) {
            if ($field = $this->once($seen, $field)) {
                yield $this->applyReadOnlyIfNeeded($field, $pageName);
            }
        }
    }

    protected function once(array &$seen, FieldInterface $field): ?FieldInterface
    {
        $dto = $field->getAsDto();
        if (!$dto instanceof FieldDto) {
            return $field;
        }

        $property = $dto->getProperty();
        if (!$property) {
            return $field; // virtual field, keep it
        }

        if (isset($seen[$property])) {
            return null;
        }

        $seen[$property] = true;
        return $field;
    }

    protected function isReadOnly(): bool
    {
        $class = static::getEntityFqcn();
        $admin = $this->ez()->getAdmin($class);

        $readOnlyByDefault = (bool)($admin['readOnlyByDefault'] ?? true);
        if (!$readOnlyByDefault) {
            return false;
        }

        foreach ($this->editRoles() as $role) {
            if ($this->security()->isGranted($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    protected function editRoles(): array
    {
        $class = static::getEntityFqcn();
        $admin = $this->ez()->getAdmin($class);

        $roles = $admin['editRoles'] ?? null;
        if (\is_array($roles) && $roles) {
            return array_values(array_filter($roles, 'is_string'));
        }

        return ['ROLE_ADMIN', 'ROLE_EDITOR'];
    }

    protected function editRoleExpression(): string
    {
        $roles = $this->editRoles();
        if (count($roles) === 1) {
            return $roles[0];
        }

        // Keep it simple and deterministic; UX is handled by disable().
        // If you want expression support, override in child class.
        return 'ROLE_ADMIN';
    }

    protected function applyReadOnlyIfNeeded(FieldInterface $field, string $pageName): FieldInterface
    {
        if ($this->isReadOnly() && ($pageName === Crud::PAGE_NEW || $pageName === Crud::PAGE_EDIT)) {
            $field->setFormTypeOption('disabled', true);
        }
        return $field;
    }

    protected function buildFieldFromConfig(string $entityClass, string $name, array $cfg): ?FieldInterface
    {
        $em = $this->doctrine()->getManagerForClass($entityClass);
        $metadata = $em?->getClassMetadata($entityClass);

        // Link field
        if (!empty($cfg['linkRoute'])) {
            $label = $cfg['label'] ?? null;
            $param = $cfg['linkParam'] ?? 'id';
            $prop  = $cfg['linkProperty'] ?? null;

            $field = LinkedTextField::new($name, $label)
                ->setRoute((string)$cfg['linkRoute'], (string)$param, $prop ? (string)$prop : null);

            return $this->applyCommonHints($field, $cfg);
        }

        // Explicit field class
        if (!empty($cfg['fieldClass']) && class_exists($cfg['fieldClass'])) {
            $cls = (string)$cfg['fieldClass'];
            if (method_exists($cls, 'new')) {
                $field = $cls::new($name);
                return $this->applyCommonHints($field, $cfg);
            }
        }

        // Infer by Doctrine type
        $type = null;
        if ($metadata) {
            if ($metadata->hasField($name)) {
                $type = $metadata->getTypeOfField($name);
            } elseif ($metadata->hasAssociation($name)) {
                $type = $metadata->isSingleValuedAssociation($name) ? 'association' : 'collection';
            }
        }

        $field = match ($type) {
            'integer', 'bigint', 'smallint' => IntegerField::new($name),
            'float', 'decimal', 'numeric'   => NumberField::new($name),
            'boolean'                       => BooleanField::new($name),
            'datetime', 'datetimetz', 'date', 'time' => DateTimeField::new($name),
            'association', 'collection'     => AssociationField::new($name),
            default                         => TextField::new($name),
        };

        return $this->applyCommonHints($field, $cfg);
    }

    protected function applyCommonHints(FieldInterface $field, array $cfg): FieldInterface
    {
        if (!empty($cfg['label'])) {
            $field->setLabel((string)$cfg['label']);
        }
        if (!empty($cfg['help'])) {
            $field->setHelp((string)$cfg['help']);
        }
        if (!empty($cfg['templatePath'])) {
            $field->setTemplatePath((string)$cfg['templatePath']);
        }
        if (\array_key_exists('sortable', $cfg) && $cfg['sortable'] !== null) {
            $field->setSortable((bool)$cfg['sortable']);
        }
        return $field;
    }

    /**
     * @return string[]
     */
    private function orderedNamesForPage(array $fieldsCfg, string $pageName): array
    {
        $wanted = [];
        $key = $this->pageToKey($pageName);

        foreach ($fieldsCfg as $name => $cfg) {
            $showOn = $cfg['showOn'] ?? null;
            $hideOn = $cfg['hideOn'] ?? null;

            if ($hideOn && \in_array($key, (array)$hideOn, true)) {
                continue;
            }
            if ($showOn && !\in_array($key, (array)$showOn, true)) {
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
}
