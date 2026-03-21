# Upgrade Guide

## Upgrading from 9 to 10

Version 10 hardens several security defaults. Existing applications may
need to explicitly re-enable previously-open behaviour.

- **HTTP method restricted to POST** - Schemas now default to `'method' => ['POST']`.
  To re-enable GET requests, add `'method' => ['GET', 'POST']` to each schema in `config/graphql.php`.
- **Batching disabled** - `batching.enable` now defaults to `false`.
  Set it to `true` to restore batching.
- **Max batch size** - New `batching.max_batch_size` option (default `10`).
  Set to `null` to remove the limit.
- **Query depth limit** - `security.query_max_depth` now defaults to `13` (was `null`).
  Set to `null` to remove the limit.
- **Query complexity limit** - `security.query_max_complexity` now defaults to `500` (was `null`).
  Set to `null` to remove the limit.
- **Introspection disabled** - `security.disable_introspection` now defaults to `true`.
  To allow introspection (e.g. in dev), set env `GRAPHQL_DISABLE_INTROSPECTION=false`.
- **Introspection env var renamed** - The env var changed from
  `GRAPHQL_INTROSPECTION` to `GRAPHQL_DISABLE_INTROSPECTION` (inverted logic).
  Update `.env` files accordingly.
- **Authorization runs before validation** - Field authorization (`authorize()`) is
  now checked before argument validation rules. Unauthorized requests are
  rejected without revealing validation details.
- **Strict authorization comparison** - The `authorize()` return value is now
  compared with `!== true` (strict). Ensure your `authorize()` methods return
  an actual `bool`.
- **Cross-field validation rules in nested InputTypes** - Validation rules like
  `prohibits:otherField`, `required_without:otherField`, `required_if:field,value`,
  etc. defined on InputType fields are now automatically transformed to use
  fully-qualified dot-notation paths. This fixes cross-field rules that previously
  didn't work in nested or list InputTypes. If this causes issues, you can disable
  it per mutation/query by overriding `processCollectedRules()` to return `$rules`
  unchanged.
- **Privacy enforcement moved from `SelectFields` to field resolvers** - The
  `privacy` attribute on Type fields is now enforced universally via resolver
  wrapping in `Type::getFields()`, instead of only inside `SelectFields`. This
  means privacy now works on nested/sub-types and when `SelectFields` is not
  used. Three breaking changes:
  1. The first parameter of `Privacy::validate()` has been renamed from
     `$queryArgs` to `$fieldArgs` and now contains the **field's own arguments**
     instead of the root query's arguments. Update your Privacy subclasses:
     ```diff
     -public function validate(array $queryArgs, $queryContext = null): bool
     +public function validate(array $fieldArgs, $queryContext = null): bool
     ```
     Update any privacy logic that relied on inspecting root query arguments.
  2. Privacy closures receive the same change - `$args` now contains the field's
     own arguments, not the root query's arguments.
  3. `SelectFields` no longer excludes denied columns from the SQL `SELECT`
     statement. The column is still fetched, but the field resolver returns
     `null`. If you relied on the denied column being absent from SQL queries,
     adjust accordingly.
- `SelectFields` now identifies wrapper types (pagination types, custom wrap
  types) via the `Rebing\GraphQL\Support\Contracts\WrapType` marker interface
  instead of config lookups.
  If you use a custom pagination class (via the `pagination_type`,
  `simple_pagination_type`, or `cursor_pagination_type` config keys) or a custom
  wrap type with `GraphQL::wrapType()`, your class must
  `implement \Rebing\GraphQL\Support\Contracts\WrapType` for `SelectFields` to
  work correctly.
- **Middleware type hints** - `Middleware::handle()` and `Middleware::resolve()` now
  declare native `mixed` types for `$root`, `$context`, and the return type. If your
  middleware subclass overrides these methods without matching return types, PHP 8.1+
  will emit a deprecation notice. Add `: mixed` to your method signatures:
  ```diff
  -public function handle($root, array $args, $context, ResolveInfo $info, Closure $next)
  +public function handle(mixed $root, array $args, mixed $context, ResolveInfo $info, Closure $next): mixed
  ```

## Upgrading from v1 to v2

Although version 2 builds on the same code base and does not radically change how the library itself works, many things were improved, sometimes leading to incompatible changes.

- Step 0: make a backup!
- Re-publish the configuration file to learn about all the new settings
- The order and arguments/types for resolvers has changed:
  - before: `resolve($root, $array, SelectFields $selectFields, ResolveInfo $info)`
  - after: `resolve($root, $array, $context, ResolveInfo $info, Closure $getSelectFields)`
  - If you now want to use SelectFields, you've to first request it: `$selectFields = $getSelectFields();`. The primary reason for this is performance. SelectFields is an optional feature but consumes resources to traverse the GraphQL request AST and introspect all the types for their configuration to apply its magic. In the past it was always constructed and thus consumed resources, even when not requested. This has been changed to an explicit form.
- Many method signature declarations changed to improve type safety, which have to be adapted:
  - The signature of the method fields changed:
    - from `public function fields()`
    - to `public function fields(): array`
  - The signature of the method toType changed:
    - from `public function toType()`
    - to `public function toType(): \GraphQL\Type\Definition\Type`
  - The signature of the method getFields changed:
    - from `public function getFields()`
    - to `public function getFields(): array`
  - The signature of the method interfaces changed:
    - from `public function interfaces()`
    - to `public function interfaces(): array`
  - The signature of the method types changed:
    - from `public function types()`
    - to `public function types(): array`
  - The signature of the method type changed:
    - from `public function type()`
    - to `public function type(): \GraphQL\Type\Definition\Type`
  - The signature of the method args changed:
    - from `public function args()`
    - to `public function args(): array`
  - The signature of the method queryContext changed:
    - from `protected function queryContext($query, $variables, $schema)`
    - to `protected function queryContext()`
  - The signature of the controller method query changed:
    - from `function query($query, $variables = [], $opts = [])`
    - to `function query(string $query, ?array $variables = [], array $opts = []): array`
  - If you're using custom Scalar types:
    - the signature of the method parseLiteral changed (due to upgrade of the webonyx library):
      - from `public function parseLiteral($ast)`
      - to `public function parseLiteral($valueNode, ?array $variables = null)`
- The `UploadType` now has to be added manually to the `types` in your schema if you want to use it. The `::getInstance()` method is gone, you simple reference it like any other type via `GraphQL::type('Upload')`.
- Follow Laravel convention and use plural for namespaces (e.g. new queries are placed in `App\GraphQL\Queries`, not `App\GraphQL\Query` anymore); the respective `make` commands have been adjusted. This will not break any existing code, but code generates will use the new schema.
- Be sure to read the [Changelog](CHANGELOG.md) for more details
