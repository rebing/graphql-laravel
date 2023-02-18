CHANGELOG
=========

[Next release](https://github.com/rebing/graphql-laravel/compare/8.6.0...master)
--------------

2023-02-18, 8.6.0
-----------------
### Added
- Add Laravl 10 support [\#983 / jasonvarga](https://github.com/rebing/graphql-laravel/pull/983)

2023-01-13, 8.5.0
-----------------
### Added
- Add support for thecodingmachine/safe 2.4 [\#961 / tranvantri](https://github.com/rebing/graphql-laravel/pull/961)

2023-01-06, 8.4.0
-----------------
### Added
- Register directives via schema config [\#947 / sforward](https://github.com/rebing/graphql-laravel/pull/947)

2022-06-11, 8.3.0
-----------------
### Added
- Add support to use array in `controller` param in config [\#906 / viktorruskai](https://github.com/rebing/graphql-laravel/pull/906)
- Add support for laravel validation attributes [\#901 / jacobdekeizer](https://github.com/rebing/graphql-laravel/pull/901)

### Fixed
- Allow 'always' to work on object types [\#473 / tinyoverflow \#369 / zjbarg](https://github.com/rebing/graphql-laravel/pull/892)
- Allow using addSelect() in relationship query scopes [\#875 / codercms](https://github.com/rebing/graphql-laravel/pull/806)

### Removed
- Support for PHP 7.2, PHP 7.3 and Laravel 7.0 (all EOL) [\#914 / mfn](https://github.com/rebing/graphql-laravel/pull/914)

2022-01-30, 8.2.1
-----------------
### Fixed
- Fix schema parsing issue when route prefix is empty string [\#890 / hello-liang-shan](https://github.com/rebing/graphql-laravel/pull/890)\
  Note: this is a follow-up fix to #888

2022-01-27, 8.2.0
-----------------
### Fixed
- Fix "No configuration for schema '' found" when route prefix is empty string [\#888 / hello-liang-shan](https://github.com/rebing/graphql-laravel/pull/888)

2022-01-15, 8.1.0
-----------------
### Added
- Support for Laravel 9 [\#879 / mfn](https://github.com/rebing/graphql-laravel/pull/879)

2021-11-15, 8.0.0
-----------------

## Breaking changes
- Rewrite and simplify how schemas are handled
  - `\Rebing\GraphQL\GraphQL::$schemas` now only holds `Schema`s and not a
    mixture of strings or arrays
  - `\Rebing\GraphQL\GraphQL::schema()` now only accepts a "schema name", but no
    ad hoc `Schema` or "schema configs". To use ad hoc schemas, use
    `\Rebing\GraphQL\GraphQL::buildSchemaFromConfig()` and
    `\Rebing\GraphQL\GraphQL::addSchema()`
  - `\Rebing\GraphQL\GraphQL::queryAndReturnResult()` (and thus also
    `\Rebing\GraphQL\GraphQL::query()`) does not accept ad hoc schemas via
    `$opts['schema']` anymore; it now only can reference a schema via its name.
  - `\Rebing\GraphQL\GraphQL::addSchema()` now only accept `Schema` objects,
    where before it would support ad hoc schemas via array configuration.
    Use `\Rebing\GraphQL\GraphQL::buildSchemaFromConfig()` for that now.
  - `\Rebing\GraphQL\GraphQL::getSchemaConfiguration()` has been removed due to
    the simplifications.
  - `\Rebing\GraphQL\GraphQL::getNormalizedSchemaConfiguration()` does not
    support ad hoc schemas anymore and only accepts the schema name.
  - `\Rebing\GraphQL\GraphQLServiceProvider::bootSchemas()` has been removed due
    to the simplifications.
    
- The following methods now take a `\Illuminate\Contracts\Config\Repository` as
  second argument:
  - `\Rebing\GraphQL\GraphQL::__construct`
  - `\Rebing\GraphQL\GraphQLServiceProvider::applySecurityRules`
- As part of moving the architecture to an execution based middleware approach,
  the following methods have been removed:
  - `\Rebing\GraphQL\GraphQLController::handleAutomaticPersistQueries` has been
    replaced by the `AutomaticPersistedQueriesMiddleware` middleware
  - `\Rebing\GraphQL\GraphQLController::queryContext` has been
    replaced by the `AddAuthUserContextValueMiddleware` middleware\
    If you relied on overriding `queryContext` to inject a custom context, you
    now need to create your own execution middleware and add to your
    configuration
  - `\Rebing\GraphQL\GraphQLController::executeQuery` has become obsolete, no
    direct replacement.

- Routing has been rewritten and simplified [\#757 / mfn](https://github.com/rebing/graphql-laravel/pull/757)
  - All routing related configuration is now within the top level `route`
    configuration key
  - The following configuration options have been removed:
    - `graphql.routes`\
      It's therefore also not possible anymore to register different routes for
      queries and mutations within a schema. Each schema gets only one route
      (except for the default schema, which is registered for the global prefix
      route as well as under its name).\
      If necessary, this can be emulated with different schemas and multi-level
      paths
  - The following configuration options have been moved/renamed:
    - `graphql.prefix` => `graphql.route.prefix`
    - `graphql.controllers` => `graphql.route.controller`\
      Further, providing a controller action for `query` or `mutation` is not
      supported anymore.
    - `graphql.middleware` => `graphql.route.middleware`
    - `graphql.route_group_attributes` => `graphql.route.group_attributes`
  - The actual routes defined have changed:
    - No more separate routes for the HTTP methods
    - 1 route for each schema + 1 route for the group prefix (default schema)
    - If GraphiQL is enabled: 1 route graphiql route for each schema + 1 for the
      graphiql group prefix (default schema)
    - If provided, the `'method'` argument **must** provide the HTTP method
      verbs in uppercase like `POST` or `GET`, `post` or `get` will **not** work.
  - It's now possible to prevent the registering of any routes by making the top
    level `route` an empty array or null
  - `\Rebing\GraphQL\GraphQL::routeNameTransformer` has been removed
  - It's now possible to register schemas with a `-` in their name
  - Routes are now properly cacheable

- Remove the `\Rebing\GraphQL\GraphQLController::$app`  property [\#755 / mfn](https://github.com/rebing/graphql-laravel/pull/755)\
  Injecting the application container early is incompatible when running within
  an application server like laravel/octane, as it's not guaranteed that the
  container received contains all the bindings. If you relied on this property
  when extending the classes, invoke the container directly via
  `Container::getInstance()`.
  
- Remove deprecated `\Rebing\GraphQL\Support\Type::$inputObject` and `\Rebing\GraphQL\Support\Type::$enumObject` properties [\#752 / mfn](https://github.com/rebing/graphql-laravel/pull/752)\
  Instead in your code, extend `\Rebing\GraphQL\Support\InputType` and `\Rebing\GraphQL\Support\EnumType` directly 
  
- Support for Lumen has been removed
  
- Integrate laragraph/utils RequestParser [\#739 / mfn](https://github.com/rebing/graphql-laravel/pull/739)\
  The parsing of GraphQL requests is now more strict:
  - if you send a `GET` request, the GraphQL query has to be in the query parameters
  - if you send a `POST` request, the GraphQL query needs to be in the body\
    Mixing of either isn't possible anymore
  - batched queries will only work with `POST` requests
    This is due to `RequestParser` using `\GraphQL\Server\Helper::parseRequestParams` which includes this check
  Further:  
  - Drop support for configuration the name of the variable for the variables (`params_key`)
  - `GraphQLUploadMiddleware` has been removed (`RequestParser` includes this functionality)
  - Empty GraphQL queries now return a proper validated GraphQL error
      
- In `\Rebing\GraphQL\GraphQL`, renamed remaining instances of `$params` to `$variables`    
  After switching to `RequestParser`, the support for changing the variable name
  what was supposed to `params_key` has gone and thus the name isn't fitting anymore.
  Also, the default value for `$variables` has been changed to `null` to better
  fit the how `OperationParams` works:
  - old: `public function query(string $query, ?array $params = [], array $opts = []): array`
    new: `public function query(string $query, ?array $variables = null, array $opts = []): array`
  - old: `public function queryAndReturnResult(string $query, ?array $params = [], array $opts = []): ExecutionResult`
    new: `public function queryAndReturnResult(string $query, ?array $variables = null, array $opts = []): ExecutionResult`

  - `\Rebing\GraphQL\Support\ResolveInfoFieldsAndArguments` has been removed
  - `$getSelectFields` closure no longer takes a depth parameter

- The `$args` argument, of the `handle` method of the execution middlewares requires `array` as type.  

### Added
- Command to make an execution middleware [\#772 / mfn](https://github.com/rebing/graphql-laravel/pull/772)
- Command to make a schema configuration [\#830 / matsn0w](https://github.com/rebing/graphql-laravel/pull/830)
- The primary execution of the GraphQL request is now piped through middlewares [\#762 / crissi and mfn](https://github.com/rebing/graphql-laravel/pull/762)\
  This allows greater flexibility for enabling/disabling certain functionality
  as well as bringing in new features without having to open up the library.
- Primarily register \Rebing\GraphQL\GraphQL as service and keep `'graphql'` as alias [\#768 / mfn](https://github.com/rebing/graphql-laravel/pull/768)
- Automatic Persisted Queries (APQ) now cache the parsed query [\#740 / mfn](https://github.com/rebing/graphql-laravel/pull/740)\
  This avoids having to re-parse the same queries over and over again.
- Add ability to detect unused GraphQL variables [\#660 / mfn](https://github.com/rebing/graphql-laravel/pull/660)
- Laravel's `ValidationException` is now formatted the same way as a `ValidationError` [\#748 / mfn](https://github.com/rebing/graphql-laravel/pull/748)
- A few missing typehints (mostly array related) [\#849 / mfn](https://github.com/rebing/graphql-laravel/pull/849)

### Changed
- Internally webonyx query plan feature is now used for retrieving information about a query [\#793 / crissi](https://github.com/rebing/graphql-laravel/pull/793))
- Rewrite and simplify how schemas are handled [\#779 / mfn](https://github.com/rebing/graphql-laravel/pull/779)
- Internally stop using the global `config()` function and preferable use the repository or the Facade otherwise [\#774 / mfn](https://github.com/rebing/graphql-laravel/pull/774)
- Don't silence broken schemas when normalizing them for generating routes [\#766 / mfn](https://github.com/rebing/graphql-laravel/pull/766)
- Lazy loading types has been enabled by default [\#758 / mfn](https://github.com/rebing/graphql-laravel/pull/758)
- Make it easier to extend select fields [\#799 / crissi](https://github.com/rebing/graphql-laravel/pull/799)
- The `$args` argument, of the `handle` method of the execution middlewares requires `array` as type [\#843 / sforward](https://github.com/rebing/graphql-laravel/pull/843)
- Embrace thecodingmachine/safe and use thecodingmachine/phpstan-safe-rule to enforce it [\#851 / mfn](https://github.com/rebing/graphql-laravel/pull/851)
- Don't require a return value for the query option of fields [\#856 / sforward](https://github.com/rebing/graphql-laravel/pull/856)

### Fixed
- Fix `TypeNotFound` when an interface defined after another type where it is used [\#828 / kasian-sergeev](https://github.com/rebing/graphql-laravel/pull/828)

### Removed
- The method `\Rebing\GraphQL\GraphQLServiceProvider::provides` was removed [\#769 / mfn](https://github.com/rebing/graphql-laravel/pull/769)\
  It's only relevant for deferred providers which ours however isn't (and can't
  be made into with the current Laravel architecture).

2021-04-10, 7.2.0
-----------------
### Added
- Allow disabling batched requests [\#738 / mfn](https://github.com/rebing/graphql-laravel/pull/738)

2021-04-08, 7.1.0
-----------------
### Added
- Basic Automatic Persisted Queries (APQ) support [\#701 / illambo](https://github.com/rebing/graphql-laravel/pull/701)

2021-04-03, 7.0.1
-----------------
### Added
- Support Laravels simple pagination [\#715 / lamtranb](https://github.com/rebing/graphql-laravel/pull/715)

2021-04-03, 7.0.0
-----------------
## Breaking changes
- Signature of `\Rebing\GraphQL\Support\Privacy::validate` changed, now it accepts both query/mutation arguments and the query/mutation context.
  Update your existing privacy policies this way:
  ```diff
  -public function validate(array $queryArgs): bool
  +public function validate(array $queryArgs, $queryContext = null): bool
  ```

### Added
- Ability to pass query/mutation context to the field privacy handler (both closure and class) [\#727 / torunar](https://github.com/rebing/graphql-laravel/pull/727)

2021-04-03, 6.5.0
-----------------
### Fixed
- Middleware and methods can be used in class based schemas. [\#724 / jasonvarga](https://github.com/rebing/graphql-laravel/pull/724)\
  This is a follow-up fix for [Support for class based schemas](https://github.com/rebing/graphql-laravel/pull/706)

2021-03-31, 6.4.0
-----------------
### Added
- Support for per-schema types [\#658 / stevelacey](https://github.com/rebing/graphql-laravel/pull/658)

2021-03-12, 6.3.0
-----------------
### Added
- Support for class based schemas [\#706 / jasonvarga](https://github.com/rebing/graphql-laravel/pull/706)

2021-03-12, 6.2.0
-----------------
### Fixed
- Lumen routing with regular expression constraints [\#719 / sglitowitzsoci](https://github.com/rebing/graphql-laravel/pull/719)

2020-11-30, 6.1.0
-----------------
Same as 6.1.0-rc1!

### Added
- Support for resolver middleware [\#594 / stevelacey](https://github.com/rebing/graphql-laravel/pull/594)

2020-11-27, 6.1.0-rc1
---------------------
### Added
- Support for resolver middleware [\#594 / stevelacey](https://github.com/rebing/graphql-laravel/pull/594)

2020-11-26, 6.0.0
-----------------
### Fixed
- Implemented generation of a SyntaxError instead of a hard Exception for empty single/batch queries [\#685 / plivius](https://github.com/rebing/graphql-laravel/pull/685)

2020-11-13, 6.0.0-rc1
---------------------
## Breaking changes
- Upgrade to webonyx/graphql-php 14.0.0 [\#645 / mfn](https://github.com/rebing/graphql-laravel/pull/645)
  Be sure to read up on breaking changes in graphql-php => https://github.com/webonyx/graphql-php/releases/tag/v14.0.0
- Remove support for Laravel < 6.0 [\#651 / mfn](https://github.com/rebing/graphql-laravel/pull/651)
  This also bumps the minimum required version to PHP 7.2

### Added
- Support for Laravel 8 [\#672 / mfn](https://github.com/rebing/graphql-laravel/pull/672)

2020-11-26, 5.1.5
-----------------
### Fixed
- Implemented generation of a SyntaxError instead of a hard Exception for empty single/batch queries [\#685 / plivius](https://github.com/rebing/graphql-laravel/pull/685)

2020-11-16, 5.1.5-rc1
---------------------
### Added
- Support for PHP 8 [\#686 / mfn](https://github.com/rebing/graphql-laravel/pull/686)

2020-09-03, 5.1.4
-----------------
Hotfix release to replace 5.1.3

Apologies for the rushed 5.1.3 release causing trouble, it was in fact cut from the wrong branch and it was current state for the upcoming 6.x series 😬

5.1.4 intends to correct this.

### Added
- Support Laravel 8 [\#671 / mfn](https://github.com/rebing/graphql-laravel/pull/671)

2020-09-02, 5.1.3
-----------------
### Added
- Support Laravel 8 [\#671 / mfn](https://github.com/rebing/graphql-laravel/pull/671)

2020-07-02, 5.1.2
-----------------
### Added
- Re-added support for validation in field arguments (with breaking change fix) [\#630 / crissi](https://github.com/rebing/graphql-laravel/pull/630)

2020-04-23, 5.1.1
-----------------
### Fixed
- Reverted "Add support for validation in field arguments" due to [breaking changes reported](https://github.com/rebing/graphql-laravel/issues/627)

2020-04-22, 5.1.0
-----------------
### Added
- Add support for validation in field arguments [\#608 / crissi](https://github.com/rebing/graphql-laravel/pull/608)
- Add support for modifiers to `GraphQL::type` [\#621 / stevelacey](https://github.com/rebing/graphql-laravel/pull/621)

2020-04-03, 5.0.0
-----------------
### Added
- Support Laravel 7 [\#597 / exodusanto](https://github.com/rebing/graphql-laravel/pull/597)
- Add support for custom authorization message [\#578 / Sh1d0w](https://github.com/rebing/graphql-laravel/pull/578)
- Add support for macros on the GraphQL service/facade [\#592 / stevelacey](https://github.com/rebing/graphql-laravel/pull/592)
### Fixed
- Fix the infinite loop as well as sending the correct matching input data to the rule-callback [\#579 / crissi](https://github.com/rebing/graphql-laravel/pull/579)
- Fix selecting not the correct columns for interface fields [\#607 / illambo](https://github.com/rebing/graphql-laravel/pull/607)
### Changed
- Refactor route files with the goal of making adding subscription support easier [\#575 / crissi](https://github.com/rebing/graphql-laravel/pull/575)
### Removed
- Official support for Laravel 5.8 has been removed [\#596 / mfn](https://github.com/rebing/graphql-laravel/pull/596)

2019-12-09, 4.0.0
-----------------
### Added
- Allow passing through an instance of a `Field` [\#521 / georgeboot](https://github.com/rebing/graphql-laravel/pull/521/files)
- Add the ability to alias query and mutations arguments as well as input objects [\#517 / crissi](https://github.com/rebing/graphql-laravel/pull/517/files)
- Classes can now be injected in the Resolve method from the query/mutation similarly to Laravel controller methods [\#520 / crissi](https://github.com/rebing/graphql-laravel/pull/520/files)
### Fixed
- Fix validation rules for non-null list of non-null objects [\#511 / crissi](https://github.com/rebing/graphql-laravel/pull/511/files)
- Add morph type to returned models [\#503 / crissi](https://github.com/rebing/graphql-laravel/pull/503)
- Querying same field multiple times causes an error (e.g. via fragments) [\#537 / edgarsn](https://github.com/rebing/graphql-laravel/pull/537)
- Fixed the custom query not being handled by interface's relations [\#486 / EdwinDayot](https://github.com/rebing/graphql-laravel/pull/486)
### Changed
- Switch Code Style handling from StyleCI to PHP-CS Fixer [\#502 / crissi](https://github.com/rebing/graphql-laravel/pull/502)
- Implemented [ClientAware](https://webonyx.github.io/graphql-php/error-handling/#default-error-formatting) interface on integrated exceptions [\#530 / georgeboot](https://github.com/rebing/graphql-laravel/pull/530)
- More control over validation through optional user-generated validator by introducing `getValidator()` [\#531 / mailspice](https://github.com/rebing/graphql-laravel/pull/531)

2019-10-23, 3.1.0
-----------------
### Added
- Allow passing through the `rootValue` as an option [\#492 / tuurbo](https://github.com/rebing/graphql-laravel/pull/492)

2019-10-20, 3.0.0
-----------------
### Added
- Add `wrapType()`, allowing to add more information for queries/mutations [\#496 / albertcito](https://github.com/rebing/graphql-laravel/pull/496)
### Changed
- The signature of `authorize` changed, receiving not the exact same argumenst the resolver would [\#489 / mfn](https://github.com/rebing/graphql-laravel/pull/489)
  - before: `public function authorize(array $args)`
  - after: `public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool`
- Forward PHP engine errors to the application error handler [\#487 / mfn](https://github.com/rebing/graphql-laravel/pull/487)

2019-08-27, 2.1.0
-----------------
### Added
- The custom `'query'` now receives the GraphQL context as the 3rd arg (same as any resolver) [\#464 / mfn](https://github.com/rebing/graphql-laravel/pull/464)
- Allow to load deeper nested queries by allowing to change the depth when calling `$getSelectFields(int $depth)` [\#472 / mfn](https://github.com/rebing/graphql-laravel/pull/472)

2019-08-18, 2.0.1
-----------------
### Added
- Allow `'alias'` to be a callback [\#452 / crissi](https://github.com/rebing/graphql-laravel/pull/452)

### Changed
- Internal
  - Properly separate larastans' phpstan config from ours [\#451 / szepeviktor](https://github.com/rebing/graphql-laravel/pull/451)

### Fixed
- Support adding Schema objects directly [\#449 / mfn](https://github.com/rebing/graphql-laravel/pull/449)
- Input arguments are properly parsed when objects or lists are passed [\#419 / sowork](https://github.com/rebing/graphql-laravel/pull/419)

2019-08-05, 2.0.0
-----------------
## Breaking changes
- The `UploadType` now has to be added manually to the `types` in your schema if you want to use it
  - The `::getInstance()` method is gone
- The order and arguments/types for resolvers has changed:
  - before: `resolve($root, $array, SelectFields $selectFields, ResolveInfo $info)`
  - after: `resolve($root, $array, $context, ResolveInfo $info, Closure $getSelectFields)`
- Added PHP types / phpdoc to all methods / properties [\#331](https://github.com/rebing/graphql-laravel/pull/331)
  - Changes in method signatures will require small adaptions.
- Validation errors are moved from `error.validation` to `error.extensions.validation` as per GraphQL spec recommendation [\#294](https://github.com/rebing/graphql-laravel/pull/294)
- SelectFields on interface types now only selects specific fields instead of all [\#294](https://github.com/rebing/graphql-laravel/pull/294)
  - Although this could be considered a bug fix, it changes what columns are selected and if your code as a side effect dependent on all columns being selected, it will break

### Added
- Added support for lazy loading types (config `lazyload_types`), improve performance on large type systems [\#405](https://github.com/rebing/graphql-laravel/pull/405) but doesn't work together with type aliases or `paginate()`.
- A migration guide for the Folklore library as part of the readme
- New `make:graphql:input` command
- New `make:graphql:union` command
- New `make:graphql:interface` command
- New `make:graphql:field` command
- New `make:graphql:enum` command and dedicated `EnumType`, deprecating `$enumObject=true` in the `Type` class
- New `make:graphql:scalar` command and add more information regarding scalars to the readme
- `TypeConvertible` interface requiring to implement `toType(): \GraphQL\Type\Definition\Type`
  Existing types are not affected because they already made use of the same method/signature, but custom Scalar GraphQL types work differently and benefit from the interface
- `alias` is now also supported for relationships [\#367](https://github.com/rebing/graphql-laravel/pull/367)
- `InputType` support class which eventually replace `$inputObject=true` [\#363](https://github.com/rebing/graphql-laravel/pull/363)
- Support `DB::raw()` in `alias` fields
- GraphiQL: use regenerated CSRF from server if present [\#332](https://github.com/rebing/graphql-laravel/pull/332)
- Internal
  - Added declare(strict_types=1) directive to all files
  - Test suite has been refactored and now features Database (SQLite) tests too

### Changed
- Types and Schemas are now only booted when the `graphql` service is requested, improving performance when having this library installed but not using it in certain workloads (pure artisan commands, non-GraphQL web requests, etc.) [\#427](https://github.com/rebing/graphql-laravel/pull/427)
- Follow Laravel convention and use plural for namespaces (e.g. new queries are placed in `App\GraphQL\Queries`, not `App\GraphQL\Query` anymore); make commands have been adjusted
- Made the following classes _abstract_: `Support\Field`, `Support\InterfaceType`, `Support\Mutation`, `Support\Query`, `Support\Type`, `Support\UnionType` [\#357](https://github.com/rebing/graphql-laravel/pull/357)
- Updated GraphiQL to 0.13.0 [\#335](https://github.com/rebing/graphql-laravel/pull/335)
  - If you're using CSP, be sure to allow `cdn.jsdelivr.net` and `cdnjs.cloudflare.com`
- `ValidatorError`: remove setter and make it a constructor arg, add getter and rely on contracts
- Replace global helper `is_lumen` with static class call `\Rebing\GraphQL\Helpers::isLumen`

### Fixed
- The Paginator correctly inherits the types model so it can be used with `SelectFields` and still generates correct SQL queries [\#415](https://github.com/rebing/graphql-laravel/pull/415)
- Arguments are now validated before they're passed to `authorize()` [\#413](https://github.com/rebing/graphql-laravel/pull/413)
- File uploads now correctly work with batched requests [\#397](https://github.com/rebing/graphql-laravel/pull/397)
- Path multi-level support for Schemas works again [\#358](https://github.com/rebing/graphql-laravel/pull/358)
- SelectFields correctly passes field arguments to the custom query [\#327](https://github.com/rebing/graphql-laravel/pull/327)
  - This also applies to privacy checks on fields, the callback now receives the field arguments too
  - Previously the initial query arguments would be used everywhere

### Removed
- Removed `\Fluent` dependency on `\Rebing\GraphQL\Support\Field` [\#431](https://github.com/rebing/graphql-laravel/pull/431)
- Removed `\Fluent` dependency on `\Rebing\GraphQL\Support\Type` [\#389](https://github.com/rebing/graphql-laravel/pull/389)
- Unused static field `\Rebing\GraphQL\Support\Type::$instances`
- Unused field `\Rebing\GraphQL\Support\Type::$unionType`

2019-06-10, v1.24.0
-------------------
### Changed
- Prefix named GraphiQL routes with `graphql.` for compatibility with Folklore [\#360](https://github.com/rebing/graphql-laravel/pull/360)

2019-06-10, v1.23.0
-------------------
### Added
- New config options `headers` to send custom HTTP headers and `json_encoding_options` for encoding the JSON response [\#293](https://github.com/rebing/graphql-laravel/pull/293)
### Fixed
- SelectFields now works with wrapped types (nonNull, listOf) [\#315](https://github.com/rebing/graphql-laravel/pull/315)

2019-05-31, v1.22.0
-------------------
### Added
- Auto-resolve aliased fields [\#283](https://github.com/rebing/graphql-laravel/pull/283)
- This project has a changelog `\o/`

2019-03-07, v1.21.2
-------------------

- Allow configuring a custom default field resolver [\#266](https://github.com/rebing/graphql-laravel/pull/266)
- Routes have now given names, so they can be referenced [\#264](https://github.com/rebing/graphql-laravel/pull/264)
- Expose more fields on the default pagination type [\#262](https://github.com/rebing/graphql-laravel/pull/262)
- Mongodb support [\#257](https://github.com/rebing/graphql-laravel/pull/257)
- Add support for MorphOne relationships [\#238](https://github.com/rebing/graphql-laravel/pull/238)
- Checks for lumen when determining schema [\#247](https://github.com/rebing/graphql-laravel/pull/247)
- Internal changes:
  - Replace deprecated global `array_*` and `str_*` helpers with direct `Arr::*` and `Str::*` calls
  - Code style now enforced via [StyleCI](https://styleci.io/)

2019-03-07, v1.20.2
-------------------

- Fixed infinite recursion for InputTypeObject self reference [\#230](https://github.com/rebing/graphql-laravel/pull/230)

2019-03-03, v1.20.1
-------------------

- Laravel 5.8 support

2019-02-04, v1.19.1
-------------------

- Don't report certain GraphQL Errors

2019-02-03, v1.18.1
-------------------

- Mutation routes fix

2019-01-29, v1.18.0
-------------------

- Fix to allow recursive input objects [\#158](https://github.com/rebing/graphql-laravel/issues/158)

2019-01-24, v1.17.6
-------------------

- Fixed default error handler

2018-12-17, v1.17.3
-------------------

- Bump webonxy/graphql-php version requirement
- Add support for custom error handler config `handle_errors`

2018-12-17, v1.16.0
-------------------

- Fixed validation

2018-07-20, v1.14.2
-------------------

- Validation error messages
  Can now add custom validation error messages to Queries and Mutations

2018-05-16, v1.13.0
-------------------

- Added support for query complexity and depth ([more details](https://github.com/webonyx/graphql-php#security))
- Also added support for InputObjectType rules validation.

2018-04-20, v1.12.0
-------------------

- [Added support for Unions](https://github.com/rebing/graphql-laravel/blob/master/docs/advanced.md#unions) and [Interfaces](https://github.com/rebing/graphql-laravel/blob/master/docs/advanced.md#interfaces)

2018-04-10, v1.11.0
-------------------

- Rules supported for all Fields
  Added `rules` support for Query fields

2018-02-28, v1.9.5
------------------

- Allow subscription types to be added
  Supports creating the schema, but the underlying PHP functionality does not do anything.

2018-01-05, v1.8.2
------------------

- Updating route and controller to give us the ability to create multilevel URI names [\#69](https://github.com/rebing/graphql-laravel/pull/69)

2017-10-31, v1.7.3
------------------

- Composer fix

2017-10-04, v1.7.1
------------------

- SelectFields fix

2017-09-23, v1.6.1
------------------

- GET routes

2017-08-27, v1.5.0
------------------

- Enum types

2017-08-20, v1.4.9
------------------

- Privacy validation optimized

2017-03-27, v1.4
------------------

- Initial release
