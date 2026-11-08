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

Requires PHP 8.5, Symfony 8.1+, EasyAdmin 5.9+, Twig 3.29+ and survos/menu-bundle.
The bundle registers its slot layout by default. See [menu integration](docs/menus.md)
for listeners, page context, locale placement and replacing the listing action.
KnpMenuBundle still provides the library factory and matcher; it is not removed by this integration.

## Usage

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
