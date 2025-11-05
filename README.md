# SurvosEzBundle

Lightweight tools that extend EasyAdmin (easycorp/easyadmin-bundle).

## Features

- Define default fields via attributes
- Generate all entity crud controllers via a single command (code-bundle?)
- Automatic configuration of filters
- Base controller defaults to read-only for non-admins

## Installation

Install the bundle using Composer:

```bash
composer require survos/ez-bundle
```

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
````

## Testing

Run the test suite:

```bash
./vendor/bin/phpunit
```

## License

This bundle is released under the MIT license. See the [LICENSE](LICENSE) file for details.
