# BarcodeBundle

# ez-bundle App

Quick demonstration of survos/ez-bundle functionality.  To make this somewhat of a real-world example, this application

* uses easy-admin dashboard as the home page
* displays a QR code that when scanned opens up a mobile-friendly product page

To see it in action

* Go to ez-products.survos.com
* run the app locally (pre-configured for sqlite, this directory)
* scaffold the app in a new Symfony project.  This is the best way to see what exact steps are necessary to create the app from scratch, or what steps are needed to integrate the bundle into your own project.

## Run locally

Navigate to the ez-bundle/app directory

```bash
composer install
bin/console doctrine:schema:update
bin/console app:load
symfony server:start -d
```

If you want to test it on your local device, install ngrok or something similar.

## Scaffold using Castor

First, install castor.  The recommended way is to install the stand-alone castor application, but you can also install it as a package and run it from the vendor directory.



Instead of running the bash script above, you can create a new project, fetch the castor file, then re-build the application one file at a time.  

```bash
symfony new ez-products --webapp && cd ez-products
composer req jolicode/castor --dev
wget https://raw.githubusercontent.com/survos/ez-bundle/main/app/castor.php -O castor.php
vendor/bin/castor build
```



## Prerequisites

- PHP 8.4+
- Symfony CLI
- [Castor](https://castor.jolicode.com/) task runner
- ngrok (for testing local dev on mobile device)

## Install Castor
```bash
curl "https://castor.jolicode.com/install" | bash
```

## Quick Start
```bash
# Create a new Symfony project
symfony new barcode-demo --webapp
cd barcode-demo

# Download the demo castor file
wget https://raw.githubusercontent.com/survos/ez-bundle/main/app/castor.php

# See available tasks
castor list

# Build complete demo (installs bundle, creates files, sets up database, starts server)
castor build
```

## Individual Steps

Run these independently if you prefer:
```bash
castor setup        # Install bundle and create directories
castor copy-files   # Copy demo files from bundle
castor database     # Configure SQLite and create schema
castor import       # Load sample product data
castor open         # Start web server and open browser
castor clean        # Remove demo files (optional)
```

## What Gets Created

- `src/Entity/Product.php` - Sample entity with barcode support
- `src/Repository/ProductRepository.php` - Repository
- `src/Command/ImportProductsCommand.php` - Data import command
- `templates/products.html.twig` - Product listing template
- SQLite database with sample products

## Next Steps

Visit the opened browser to see:
- Product listing with barcodes
- EasyAdmin dashboard
- Barcode generation examples

## Cleanup
```bash
castor clean  # Removes all demo files
```

## Troubleshothy

**Castor file not found**: Make sure you're in the project root directory

**Permission denied**: Run `chmod +x castor.php`

**Bundle not installed**: Run `castor setup` first before other commands

## Demo Application

See the [demo README](castor/README.md) for step-by-step instructions.

```bash
composer req survos/barcode-bundle
```

```twig

{# as a filter #}
{{ '12345'|barcode }}

{# as a function #}
{{ barcode(random(), 2, 80, 'red' }}

```

To set default values (@todo: install recipe)
```yaml
# config/packages/barcode.yaml
barcode:
  widthFactor: 3
  height: 120
  foregroundColor: 'purple'
```

## Proof that it works

Requirements:

* Locally installed PHP 8, with GD or Imagick
* Symfony CLI
* sed (to change /app to / without opening an editor)

```bash
symfony new BarcodeDemo --webapp && cd BarcodeDemo
symfony composer req survos/barcode-bundle
symfony console make:controller AppController
sed -i "s|/app|/|" src/Controller/AppController.php 

cat <<'EOF' > templates/app/index.html.twig
{% extends 'base.html.twig' %}
{% block body %}
{{ 'test'|barcode }} or {{ barcode('test', 2, 80, 'red') }}
{% endblock %}
EOF

#echo "{{ 'test'|barcode }} or {{ barcode('test', 2, 80, 'red') }} " >> templates/app/index.html.twig
symfony server:start -d
symfony open:local
```
