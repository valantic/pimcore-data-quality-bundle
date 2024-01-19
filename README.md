# Data Quality Bundle

[![Latest Version on Packagist](https://img.shields.io/packagist/v/valantic/pimcore-data-quality-bundle.svg?style=flat-square)](https://packagist.org/packages/valantic/pimcore-data-quality-bundle)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![PHP Checks](https://github.com/valantic/pimcore-data-quality-bundle/actions/workflows/php.yml/badge.svg)](https://github.com/valantic/pimcore-data-quality-bundle/actions/workflows/php.yml)

## Installation

```json
"require" : {
    "valantic/pimcore-data-quality-bundle" : "^2.0"
}
```

Add Bundle to `bundles.php`:
```php
return [
    Valantic\DataQualityBundle\ValanticDataQualityBundle::class => ['all' => true],
];
```

- Execute: `$ bin/console pimcore:bundle:install ValanticDataQualityBundle`

## Documentation

- [User](./docs/User.md)
- [Developer](./docs/Developer.md)
