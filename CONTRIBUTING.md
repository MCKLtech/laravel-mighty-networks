# Contributing

Contributions are welcome, and are accepted via pull requests. Please review these guidelines before
submitting any pull requests.

## Process

1. Fork the repository, clone it locally, and create a feature branch.
2. Install dependencies: `composer install`
3. Make your change, and **add tests**. Pull requests without tests will not be merged.
4. Ensure the full check suite passes:

```bash
composer check      # Pint (style) + PHPStan (level 8) + PHPUnit
```

Or individually:

```bash
composer pint:test  # code style
composer phpstan    # static analysis
composer test       # test suite
```

5. Send a pull request and describe what you changed and why.

## Standards

This package follows the conventions described in *Consuming APIs in Laravel* (Ash Allen):

- `declare(strict_types=1);` in every file.
- Classes are `final` by default. Composition over inheritance.
- DTOs are `final readonly` with constructor property promotion and **no** getters/setters.
- Prefer backed enums over class constants.
- Secrets use `#[\SensitiveParameter]`.
- Custom exceptions extend Saloon's exception tree so retries continue to work.

## Testing

- Use Saloon's `MockClient` and `MockResponse`. **Never** make real network calls in the test suite —
  Mighty Networks provides no sandbox or test network, and no test credentials exist.

## Security

If you discover any security-related issues, please email security@mckl.tech instead of using the
issue tracker.

## Coding style

Laravel Pint enforces the style. Run `composer pint` before committing.
