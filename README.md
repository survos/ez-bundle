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
    Survos\SurvosEzBundle\SurvosEzBundle::class => ['all' => true],
];
```

## Usage

This bundle provides various components depending on your configuration. Check the generated service classes and controllers for specific usage examples.

## Testing

Run the test suite:

```bash
./vendor/bin/phpunit
```

## License

This bundle is released under the MIT license. See the [LICENSE](LICENSE) file for details.
