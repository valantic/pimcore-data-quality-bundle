# Data Quality Bundle

## Config

```yaml
Customer:
  email:
    Length:
      min: 10
      max: 100
    Email: ~
  name:
    Length:
      7
    NotBlank: ~
Product:
  name:
    NotBlank: ~
```
