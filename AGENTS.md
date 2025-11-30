# Agent Guidelines for graphql-laravel

## Commands
- Run all tests: `composer tests` or `vendor/bin/phpunit`
- Run single test: `vendor/bin/phpunit --filter TestClassName::testMethodName` or `vendor/bin/phpunit tests/Unit/GraphQLTest.php`
- Lint/check style: `composer lint`
- Fix code style: `composer fix-style`
- Static analysis: `composer phpstan`
- Update PHPStan baseline: `composer phpstan-baseline`

## Code Style
- PHP 8.2+, strict types required: `declare(strict_types = 1);` at file top (note spaces around `=`)
- Namespace declaration on same line as declare: `declare(strict_types = 1);` then `namespace Foo\Bar;`
- Use strict PHPDoc types: `@var array<string,mixed>`, `@var list<object>`, `@param array<string,Schema> $schemas`
- Import all classes at top; no inline `\Fully\Qualified\Names` in code
- Use strict type hints on all methods: `public function schema(?string $schemaName = null): Schema`
- PHPStan level 8 with strict rules; check baseline file for known issues
- Follow PSR-12 via php-cs-fixer with mfn/php-cs-fixer-config ruleset
- Properties: declare types explicitly with `@var` docblocks when needed for arrays/collections
- Tests: namespace `Rebing\GraphQL\Tests\Unit` or `Rebing\GraphQL\Tests\Database`, extend `TestCase`, use PHPUnit attributes like `#[DoesNotPerformAssertions]`
- Error handling: use custom exceptions in `Rebing\GraphQL\Exception\` namespace, extend appropriate base classes
- Use Laravel contracts/interfaces where available: `Illuminate\Contracts\Config\Repository` not `Config` class
