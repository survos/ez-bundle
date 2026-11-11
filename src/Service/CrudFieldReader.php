<?php

declare(strict_types=1);

namespace Survos\EzBundle\Service;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use Survos\EzBundle\Translation\FieldLabel;
use Survos\FieldBundle\Model\FieldDescriptor;
use Survos\FieldBundle\Service\FieldReader;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\FormFactoryInterface;

/** Bridges shared display metadata and native Symfony form metadata into EasyAdmin. */
final class CrudFieldReader
{
    public function __construct(
        private readonly FieldReader $fields,
        private readonly FormFactoryInterface $forms,
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function supports(string $class): bool
    {
        return [] !== $this->fields->getDescriptors($class) || [] !== $this->formProperties($class);
    }

    /** @return iterable<FieldInterface> */
    public function fields(string $class, string $page): iterable
    {
        $descriptors = [];
        foreach ($this->fields->getDescriptors($class) as $descriptor) {
            $descriptors[$descriptor->name] = $descriptor;
        }
        if (in_array($page, [Crud::PAGE_NEW, Crud::PAGE_EDIT], true)) {
            foreach ($this->formProperties($class) as $property => $attribute) {
                $name = $attribute->name ?? $property;
                $options = $attribute->options;
                $descriptor = $descriptors[$property] ?? new FieldDescriptor($property);
                if (!array_key_exists('translation_domain', $options)) {
                    $options['translation_domain'] = $descriptor->getTranslationDomain();
                }
                if (!array_key_exists('label', $options) && !isset($options['label_format'])) {
                    $options['label'] = false === $options['translation_domain']
                        ? $descriptor->getFallbackLabel()
                        : new FieldLabel($descriptor->getTranslationKey(), $descriptor->getFallbackLabel(), $options['translation_domain'] ?? $descriptor->getTranslationDomain());
                }
                if ($name !== $property) {
                    $options['property_path'] ??= $property;
                }
                $builder = null === $attribute->type
                    ? $this->forms->createBuilderForProperty($class, $name, null, $options)
                    : $this->forms->createNamedBuilder($name, $attribute->type, null, $options);
                // A generic EA field keeps display-specific configurators from replacing native form options.
                $resolvedOptions = $builder->getOptions();
                if (!array_key_exists('data', $attribute->options)) {
                    unset($resolvedOptions['data']);
                }
                yield \Survos\EzBundle\Field\NativeFormField::new($name)
                    ->setLabel($options['label'] ?? null)
                    ->setFormType($builder->getType()->getInnerType()::class)
                    ->setFormTypeOptions($resolvedOptions);
            }

            return;
        }

        $metadata = $this->metadata($class);
        foreach ($this->fields->getDescriptors($class) as $descriptor) {
            if (!$descriptor->visible) {
                continue;
            }
            $name = $descriptor->name;
            $type = $descriptor->type;
            $field = match (true) {
                $metadata?->hasAssociation($name) ?? false => AssociationField::new($name),
                enum_exists($type) => ChoiceField::new($name)->setChoices(array_combine(array_map(fn ($case) => $case->name, $type::cases()), $type::cases())),
                'int' === $type => IntegerField::new($name),
                'float' === $type => NumberField::new($name),
                'bool' === $type => BooleanField::new($name)->renderAsSwitch(false),
                is_a($type, \DateTimeInterface::class, true) => DateTimeField::new($name),
                'array' === $type => ArrayField::new($name),
                $descriptor->isEmail => EmailField::new($name),
                $descriptor->isUrl => UrlField::new($name),
                default => TextField::new($name),
            };
            $field->setSortable($descriptor->sortable && ($metadata?->hasField($name) ?? false));
            $field->setLabel(new FieldLabel($descriptor->getTranslationKey(), $descriptor->getFallbackLabel(), $descriptor->getTranslationDomain()));
            yield $field;
        }
    }

    /** @return list<string> */
    public function filterProperties(string $class): array
    {
        $metadata = $this->metadata($class);
        $names = [];
        foreach ($this->fields->getDescriptors($class) as $field) {
            if ($field->visible && $field->filterable && ($metadata?->hasField($field->name) || $metadata?->hasAssociation($field->name))) {
                $names[] = $field->name;
            }
        }

        return $names;
    }

    /** @return array<string, FormField> */
    private function formProperties(string $class): array
    {
        $fields = [];
        foreach ((new \ReflectionClass($class))->getProperties() as $property) {
            $attributes = $property->getAttributes(FormField::class);
            if ($attributes) {
                $fields[$property->getName()] = $attributes[0]->newInstance();
            }
        }

        return $fields;
    }

    private function metadata(string $class): ?ClassMetadata
    {
        return $this->doctrine->getManagerForClass($class)?->getClassMetadata($class);
    }
}
