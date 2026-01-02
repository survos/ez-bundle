<?php
declare(strict_types=1);

namespace Survos\EzBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Type\TextType;

/**
 * Renders a value as a link to a Symfony route.
 *
 * Configuration is stored in custom options:
 *  - link_route (string)
 *  - link_param (string, default 'id')
 *  - link_property (string|null) optional entity property to use for param; if null uses EA primary key value.
 */
final class LinkedTextField implements FieldInterface
{
    use FieldTrait;

    public const OPTION_LINK_ROUTE = 'link_route';
    public const OPTION_LINK_PARAM = 'link_param';
    public const OPTION_LINK_PROPERTY = 'link_property';

    public static function new(string $propertyName, ?string $label = null): self
    {
        $self = new self();
        $self->setProperty($propertyName);
        $self->setLabel($label);
        $self->setFormType(TextType::class);

        // Use our template by default
        $self->setTemplatePath('@SurvosEz/field/linked_text.html.twig');

        return $self;
    }

    public function setRoute(string $routeName, string $paramName = 'id', ?string $property = null): self
    {
        $this->setCustomOption(self::OPTION_LINK_ROUTE, $routeName);
        $this->setCustomOption(self::OPTION_LINK_PARAM, $paramName);
        $this->setCustomOption(self::OPTION_LINK_PROPERTY, $property);

        return $this;
    }
}
