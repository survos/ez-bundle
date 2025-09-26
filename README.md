# SurvosEzBundle

A Symfony bundle for ez functionality.

## Features

- Console command for CLI operations

## Installation

Install the bundle using Composer:

```bash
composer require survos/ez-bundle
```

If you're using Symfony Flex, the bundle will be automatically registered. Otherwise, add it to your `config/bundles.php`:

```php
return [
    // ...
    Survos\EzBundle\SurvosEzBundle::class => ['all' => true],
];
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
