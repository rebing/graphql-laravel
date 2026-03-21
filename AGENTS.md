# Agent Guidelines for graphql-laravel

A Laravel wrapper for `webonyx/graphql-php`. PHP 8.2+, Laravel 12+.

For development setup, commands, code style, and the PR workflow, see [CONTRIBUTING.md](CONTRIBUTING.md).

## Project Structure

```
src/                          # Production code (Rebing\GraphQL namespace)
├── GraphQL.php               # Core class: schema building, query execution, type registry
├── GraphQLController.php     # HTTP controller for GraphQL requests
├── GraphQLServiceProvider.php # Service provider (config, routes, commands)
├── Console/                  # 12 artisan make:graphql:* generators + stubs
├── Error/                    # GraphQL-layer errors (client-safe, extend graphql-php Error)
├── Exception/                # PHP exceptions (config errors, not client-safe)
└── Support/                  # Base classes, middleware, contracts, tracing, pagination
    ├── Type.php / Field.php / Query.php / Mutation.php  # Core hierarchy
    ├── InputType.php / EnumType.php / InterfaceType.php / UnionType.php
    ├── ExecutionMiddleware/  # Pipeline middleware for full execution
    ├── Tracing/              # OpenTelemetry observability
    ├── Contracts/            # TypeConvertible, ConfigConvertible
    └── Facades/              # GraphQL facade

tests/                        # Two PHPUnit suites
├── TestCase.php              # Base: extends Orchestra Testbench, no DB
├── TestCaseDatabase.php      # Base: adds SQLite in-memory DB + migrations
├── Unit/                     # Unit tests (no database)
├── Database/                 # Database tests (SQLite in-memory)
└── Support/                  # Shared fixtures: Models/, Objects/, Queries/, Types/, Traits/

config/config.php             # Publishable Laravel config (schemas, types, middleware, security, tracing)
```

## Architecture

### Class Hierarchy

```
Field (abstract)           # Core: authorize(), rules(), args(), type(), resolve()
├── Query                  # Semantic alias (empty, extends Field)
└── Mutation               # Semantic alias (empty, extends Field)

Type (abstract)            # Base for all GraphQL types: fields(), attributes(), toType()
├── InputType              # → InputObjectType
├── EnumType               # → EnumType
├── InterfaceType          # → InterfaceType (adds resolveType/types)
├── UnionType              # → UnionType (abstract types())
└── UploadType             # → ScalarType (file uploads)
```

### Two Middleware Layers

**Execution Middleware** — wraps the full GraphQL execution pipeline:
- Base: `AbstractExecutionMiddleware` in `src/Support/ExecutionMiddleware/`
- Signature: `handle(string $schemaName, Schema $schema, OperationParams $params, $rootValue, $contextValue, Closure $next): ExecutionResult`
- Built-in: `ValidateOperationParamsMiddleware`, `AutomaticPersistedQueriesMiddleware`, `AddAuthUserContextValueMiddleware`, `UnusedVariablesMiddleware`, `GraphqlExecutionMiddleware` (terminal, always last)
- Configured via `graphql.execution_middleware` config or per-schema

**Resolver Middleware** — wraps individual field resolvers:
- Base: `Middleware` in `src/Support/Middleware.php`
- Signature: `handle($root, array $args, $context, ResolveInfo $info, Closure $next)`
- Per-field: `$middleware` property on `Field`/`Query`/`Mutation`
- Global: `graphql.resolver_middleware_append` config

### Key Classes

- **`GraphQL`** (`src/GraphQL.php`): Singleton, uses `Macroable`. Type registry with modifier parsing (`GraphQL::type('[User!]!')`). Schema building, query execution, error formatting.
- **`SelectFields`** (`src/Support/SelectFields.php`): Optimizes Eloquent queries by analyzing GraphQL field selections for `select()` and `with()`.
- **`Privacy`** (`src/Support/Privacy.php`): Abstract base for field-level access control.
- **`ConfigConvertible`** (`src/Support/Contracts/ConfigConvertible.php`): Interface for class-based schema definitions (returns array with `query`, `mutation`, `types`, `middleware`, etc.).

### Resolver Conventions

- `Field::resolve()` supports dependency injection: `SelectFields`, `ResolveInfo`, `Closure`, or any container-resolvable class
- Types auto-discover resolvers via `resolve{StudlyFieldName}Field()` methods on the Type class
- Authorization: override `authorize()` on `Field`/`Query`/`Mutation`
- Validation: override `rules()` to return Laravel validation rules for args

## Test Conventions

### Base Classes

- **`TestCase`** (`tests/TestCase.php`): Extends Orchestra Testbench. Provides `$this->queries` and `$this->data` fixtures, `httpGraphql()` helper, schema assertion methods. Pre-configures `default`, `custom`, and `class_based` schemas.
- **`TestCaseDatabase`** (`tests/TestCaseDatabase.php`): Extends `TestCase`. Adds SQLite in-memory DB, runs migrations on each test. Use for any test needing Eloquent models.

### Writing Tests

- Namespace: `Rebing\GraphQL\Tests\Unit` or `Rebing\GraphQL\Tests\Database`
- Use `self::assert*()` (static calls), not `$this->assert*()`
- Use PHPUnit attributes: `#[DoesNotPerformAssertions]`, `#[DataProvider('...')]`
- Override `getEnvironmentSetUp($app)` to register test-specific schemas, types, and config (call `parent::getEnvironmentSetUp($app)` first in Database tests)
- Co-locate test-specific support classes (queries, mutations, types) in the same directory as the test
- Shared fixtures go in `tests/Support/Objects/`, `tests/Support/Queries/`, `tests/Support/Types/`

### Test Helpers

- **`httpGraphql(string $query, array $options)`**: Dispatches POST to `/graphql`. Options: `expectErrors` (bool), `httpStatusCode` (int), `variables` (array), `schemaName` (string). Auto-strips trace/file/line from errors.
- **`SqlAssertionTrait`**: Records all SQL queries. Use `assertSqlCount(int)` and `assertSqlQueries(string)`. Available in `TestCaseDatabase` subclasses.
- **`MakeCommandAssertionTrait`**: Verifies artisan make command output (file path, namespace, class, graphql name).
- **`runCommand(Command $command, array $arguments)`**: Wraps Artisan command testing with `CommandTester`.

### Models and Factories

Database tests use 4 Eloquent models in `tests/Support/Models/`: `User`, `Post`, `Comment`, `Like` with corresponding factories and migrations in `tests/Support/database/`.

## Error Handling

Two distinct layers:

- **Errors** (`src/Error/`): Extend `GraphQL\Error\Error`, implement `ProvidesErrorCategory`. These are client-safe and returned in GraphQL responses. Classes: `ValidationError` (wraps Laravel Validator), `AuthorizationError`, `AutomaticPersistedQueriesError`.
- **Exceptions** (`src/Exception/`): Extend `RuntimeException`. These indicate configuration/developer errors and are NOT client-safe. Classes: `SchemaNotFound`, `TypeNotFound`.

## Artisan Make Commands

All generators follow `make:graphql:{type} {name}` pattern with stubs in `src/Console/stubs/`:

| Command | Default Namespace |
|---------|-------------------|
| `make:graphql:type` | `App\GraphQL\Types` |
| `make:graphql:query` | `App\GraphQL\Queries` |
| `make:graphql:mutation` | `App\GraphQL\Mutations` |
| `make:graphql:enum` | `App\GraphQL\Enums` |
| `make:graphql:input` | `App\GraphQL\Inputs` |
| `make:graphql:interface` | `App\GraphQL\Interfaces` |
| `make:graphql:union` | `App\GraphQL\Unions` |
| `make:graphql:scalar` | `App\GraphQL\Scalars` |
| `make:graphql:field` | `App\GraphQL\Fields` |
| `make:graphql:middleware` | `App\GraphQL\Middleware` |
| `make:graphql:executionMiddleware` | `App\GraphQL\Middleware\Execution` |
| `make:graphql:schemaConfig` | `App\GraphQL\Schemas` |
