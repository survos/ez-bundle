# SurvosEzBundle

Lightweight tools that extend EasyAdmin (easycorp/easyadmin-bundle).

## Features

- Event-driven menu slots across desktop/mobile headers, page navigation, actions, sidebar and footer
- Define default fields via attributes
- Generate all entity crud controllers via a single command (code-bundle?)
- Automatic configuration of filters
- Base controller defaults to read-only for non-admins

## Installation

Install the bundle using Composer:

```bash
composer require survos/ez-bundle
```

## Menu integration

Requires PHP 8.5, Symfony 8.2+, EasyAdmin 5.9+, Twig 3.29+ and survos/menu-bundle.
The bundle registers its slot layout by default. See [menu integration](docs/menus.md)
for listeners, page context, locale placement and replacing the listing action.
KnpMenuBundle still provides the library factory and matcher; it is not removed by this integration.

## FieldReader and native forms

Requires `survos/field-bundle` and Symfony Form 8.2. Both EZ base CRUD controllers
now use FieldReader when descriptors or native FormField attributes are present:

```php
use Survos\FieldBundle\Attribute\Field;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Validator\Constraints as Assert;

#[Field(order: 10, sortable: true, filterable: true)]
#[FormField]
#[Assert\PositiveOrZero]
public int $stock = 0;
```

- Index/detail pages use visible descriptors in their declared order. Unlisted
  Doctrine fields are not appended, including JSONB caches and backing properties.
- New/edit pages include only properties with FormField. Symfony's form factory
  guesses types, required/empty values and options; explicit types/options are honored.
  Form names can differ using `name`, mapped back with `property_path`.
- `Field(visible: false)` controls display; only adding FormField opts a property
  into editing. A display-only model therefore has no automatically editable fields.
- Sorting and filters require actual Doctrine-mapped properties. Virtual hooks can
  render and edit, but are not automatically translated into SQL backing-column names.
- Keep thin controllers: override `preferredFields()` on AbstractEzCrudController
  for fields that should win, or override `configureFields()` and retain parent fields.
- Models without either metadata source retain the legacy EzField/EA behavior.

This bridges property-level native form attributes into EasyAdmin's existing CRUD
form. It does not register entity classes as AsFormType services or replace EA's
submission, validation, CSRF, persistence or permissions. Standalone readonly DTO
forms continue to use Symfony's AsFormType directly. Attribute options are PHP values;
container service references in options are not resolved by this runtime bridge.

The internal DummyJSON app (`~/sites/dummy`) exercises this with Product, Babel
property hooks, an Imagor thumbnail override and authenticated edit/validation tests.

## Legacy usage

```php
use Survos\EzBundle\Attribute\EzAdmin;
use Survos\EzBundle\Attribute\EzField;
use Survos\EzBundle\Attribute\Page;

#[EzAdmin(icon: 'fa-regular fa-image', defaultSort: ['year' => 'DESC'], indexMax: 12)]
class ForteObj
{
    #[EzField(index: true, order: 1, filter: true)]
    public ?int $year = null;

    #[EzField(index: true, order: 2)]
    public ?string $title = null;
}
```

## Testing

Run the test suite:

```bash
./vendor/bin/phpunit
```

## License

This bundle is released under the MIT license. See the [LICENSE](LICENSE) file for details.
