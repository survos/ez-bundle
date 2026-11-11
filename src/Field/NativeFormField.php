<?php

declare(strict_types=1);

namespace Survos\EzBundle\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use Symfony\Contracts\Translation\TranslatableInterface;

/** An EA container for a native form field, without display-specific configurators. */
final class NativeFormField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, TranslatableInterface|string|bool|null $label = null): self
    {
        return (new self())->setProperty($propertyName)->setLabel($label)->setTemplateName('crud/field/text');
    }
}
