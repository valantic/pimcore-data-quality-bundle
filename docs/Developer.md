# Developer Docs

## Installation

Open `composer.json` and add the following snippet:

```json
{
  "repositories": [
    { "type": "vcs", "url": "https://git.cec.valantic.com/valantic-pimcore/data-quality-bundle" }
  ],
  "require": {
    "valantic-pimcore/data-quality-bundle": "*",
  }
}
```

And run `composer update`.

You can then enable and install the bundle in the Pimcore Backend (Tools > Bundles). Installing will add a new permission, `plugin_valantic_dataquality_config`.

## Configuration

The bundle's configuration is persisted in `var/config/valantic_dataquality_config.yml` allowing for it to be tracked in your VCS of choice. A configuration may look as follows:

```yaml
constraints:
  Customer:
    email:
      Length:
        min: 10
        max: 100
      Email: ~
    name:
      NotBlank: ~
      SomeValidator: ~
      Length:
        min: 4
        max: 10
    some_attribute:
      Length: 7
      NotBlank: ~
      \AppBundle\DataQuality\Custom\Validator\NonsenseConstraint: qwertz
  Product:
    name:
      NotBlank: ~
      Length:
        min: 3
    description:
      NotBlank: ~
    teaser:
      \AppBundle\DataQuality\Custom\Validator\NonsenseConstraint:
        expected: asdf
        allowed: fdsa
meta:
  Product:
    locales:
      - de
      - en
    threshold_green: 0.95
    threshold_orange: 0.5
    nesting_limit: 2
  Customer:
    locales:
      - en
      - de
    threshold_green: 0.9
    threshold_orange: 0.6
    nesting_limit: 3
```

All options can be configured in Pimcore's backend.

## Custom Constraints

To create your own constraint, please follow [Symfony's tutorial](https://symfony.com/doc/4.4/validation/custom_constraint.html). Your **constraint** should extend `Valantic\DataQualityBundle\Repository\AbstractCustomConstraint` and may implement the following methods:

```php
public function defaultParameter(): ?string
{
    return 'name_of_default_parameter';
}

public function optionalParameters(): ?array
{
    return ['optional_string_parameter' => 'hint for user', 'optional_array_parameter' => ['hint', 'for', 'user'], 'optional_boolean_parameter' => true, 'optional_numeric_parameter' => 3.14];
}

public function requiredParameters(): ?array
{
    return ['name_of_default_parameter' => 'hint for user'];
}
```

**Please note: this bundle was written with robustness as a primary goal. Hence, if your constraint or validator throws an exception, it will not be counted towards the score. Instead use standard Symfony Validator violations (`$this->context->buildViolation(...)`) to report a failing constraint.**

Your validator needs to be tagged with `validator.constraint_validator` for it to be picked up the service container and the bundle.

```yaml
    AppBundle\DataQuality\Custom\Validator\:
        resource: '../../DataQuality/Custom/Validator'
        tags: ['validator.constraint_validator']
```

Any constraint/validator for this bundle can also be used as a standard Symfony constraint/validator.

## Robustness

As aforementioned, this bundle was written with robustness as a primary goal. Hence a lot of exceptions will be swalled without further ado. In some instances, events are emitted instead. These events are:

- `Valantic\DataQualityBundle\Event\ConstraintFailureEvent`
- `Valantic\DataQualityBundle\Event\InvalidConfigEvent`
- `Valantic\DataQualityBundle\Event\InvalidConstraintEvent`

## PHP API

**Please note: this bundle was written with robustness as a primary goal. Hence, these methods will not throw an exception on failure, instead the writers return a boolean and the readers an empty array or null (generally speaking; please always check the methods signature for its return type.)**


### Scoring

Should you wish to programmatically score objects, you may find the following snippet helpful:

```php
$validation = new \Valantic\DataQualityBundle\Validation\DataObject\Validate(); // use service injection
$obj = \Pimcore\Model\DataObject::getById($id);
$validation->setObject($obj);

$validation->validate();

$validation->attributeScores();
$validation->score();
$validation->color();
$validation->scores();
```
