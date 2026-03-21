# Contributing to graphql-laravel

Contributions are welcome and appreciated. This guide covers the development
workflow and what we expect from pull requests.

## Communication Channels

You can find help and discussion in the following places:

- Support questions: [GitHub Discussions](https://github.com/rebing/graphql-laravel/discussions)
- Bug reports: <https://github.com/rebing/graphql-laravel/issues>

Blank issues are disabled. Please use the issue templates.

## Reporting Bugs

Report bugs using the [Bug Report](https://github.com/rebing/graphql-laravel/issues/new?template=1_Bug_report.md) issue template. Include your
graphql-laravel, Laravel, and PHP versions, plus steps to reproduce.

## Requesting Features

Request features using the [Feature Request](https://github.com/rebing/graphql-laravel/issues/new?template=2_Feature_request.md) issue template. If
you're willing to work on the feature yourself, mention it in the issue so we
can discuss the approach before you invest time.

## Fixing Bugs & Adding Features

This project welcomes pull requests for bug fixes and new features.

When working on your change, please keep the following in mind:

- Your pull request description should clearly detail the changes you have
  made.
- Please **write tests** for any new features or bug fixes you add.
- Please **ensure that tests pass** before submitting your pull request.
  _Hint: run `composer tests`._
- **Use topic/feature branches.** Please do not ask to pull from your main
  branch.
- **Submit one concern per pull request.** If you have multiple fixes or
  features, please break them into separate pull requests.
- **Add a CHANGELOG entry** if your change affects users of the library (new
  features, bug fixes, breaking changes, removed functionality). Internal
  changes like CI configuration, test refactoring, or documentation fixes do
  not need a CHANGELOG entry.

  Open `CHANGELOG.md` and add your change under the `[Next release]` heading,
  in the appropriate category (`Added`, `Fixed`, `Breaking changes`,
  `Removed`). Use this format:

  ```markdown
  - Description of the change [\#123 / your-github-username](https://github.com/rebing/graphql-laravel/pull/123)
  ```

- **Fix code style** before submitting. _Hint: run `composer fix-style`._
- **CI must pass.** All three workflows (tests, analysis, integration) must be
  green before a PR can be merged.

## Developing

To develop this project, you will need [PHP](https://www.php.net) 8.2 or newer
with the `pdo_sqlite` extension, and [Composer](https://getcomposer.org).

After cloning this repository locally, execute the following commands:

```bash
cd /path/to/graphql-laravel
composer install
```

All dev tooling (PHPUnit, PHPStan, php-cs-fixer, paratest) is installed via
Composer.

### Commands

| Command | What it does |
|---------|--------------|
| `composer tests` | Run the full test suite via paratest (parallel) |
| `composer fix-style` | Auto-fix code style (php-cs-fixer) |
| `composer lint` | Check code style without modifying files |
| `composer phpstan` | Run static analysis (level 8) |
| `composer phpstan-baseline` | Regenerate the PHPStan baseline |

### Coding Standards

This project follows [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards, enforced by
[php-cs-fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) with the
[`mfn/php-cs-fixer-config`](https://github.com/mfn/php-cs-fixer-config) shared
ruleset.

Key conventions:

- **Strict types** are required in every file:
  ```php
  declare(strict_types = 1);
  ```

- **Import all classes** at the top of the file. Do not use inline fully
  qualified names like `\Illuminate\Support\Facades\Config`.

- **Strict type hints** on all method parameters and return types.

- **PHPDoc for complex types** -- use generic syntax for arrays and
  collections:
  ```php
  /** @var array<string,mixed> */
  /** @var list<object> */
  /** @param array<string,Schema> $schemas */
  ```

- **Use Laravel contracts/interfaces** (e.g.
  `Illuminate\Contracts\Config\Repository`) rather than concrete classes.

- **Use `thecodingmachine/safe` functions** where applicable (e.g.
  `Safe\preg_match` instead of `preg_match`).

### Static Analysis

This project uses [PHPStan](https://github.com/phpstan/phpstan) at **level 8**
to provide static analysis of PHP code.

You may run static analysis manually with the following command:

```bash
composer phpstan
```

If you need to update the baseline, run `composer phpstan-baseline`.

### Running Tests

The test suite has two base classes:

- `TestCase` -- for tests that do not need a database.
- `TestCaseDatabase` -- adds an SQLite in-memory database with migrations.

To run all the tests, execute the following from the command line, while in the
project root directory:

```bash
composer tests
```

Three GitHub Actions workflows run on every PR:

1. **Tests** (`tests.yml`) -- PHPUnit across PHP 8.2--8.5, Laravel 12--13,
   with both `prefer-lowest` and `prefer-stable` dependency resolution.
2. **Analysis** (`analysis.yml`) -- PHPStan static analysis, code style lint,
   and `composer audit` (security).
3. **Integration tests** (`integration_tests.yml`) -- Runs against a real
    Laravel application and verifies OpenTelemetry integration.
