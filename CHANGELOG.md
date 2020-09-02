CHANGELOG
=========

[Next release](https://github.com/rebing/graphql-laravel/compare/5.1.3...master)
--------------

2020-09-02, 5.1.3
-----------------
### Added
- Support Laravel 8 [\#671 / mfn](https://github.com/rebing/graphql-laravel/pull/671)

2020-07-02, 5.1.2
-----------------
### Added
- Readded support for validation in field arguments (with breaking change fix) [\#630 / crissi](https://github.com/rebing/graphql-laravel/pull/630)

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
- Validation errors are moved from error.validation to error.extensions.validation as per GraphQL spec recommendation [\#294](https://github.com/rebing/graphql-laravel/pull/294)
- SelectFields on interface types now only selects specific fields instead of all [\#294](https://github.com/rebing/graphql-laravel/pull/294)
  - Although this could be consider a bug fix, it changes what columns are selected and if your code as a side-effect dependent on all columns being selected, it will break

### Added
- Added support for lazy loading types (config `lazyload_types`), improve performance on large type systems [\#405](https://github.com/rebing/graphql-laravel/pull/405) but doens't work together with type aliases or `paginate()`.
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
- Follow Laravel convention and use plural for namspaces (e.g. new queries are placed in `App\GraphQL\Queries`, not `App\GraphQL\Query` anymore); make commands have been adjusted
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
- Routes have now given names so they can be referenced [\#264](https://github.com/rebing/graphql-laravel/pull/264)
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
- Updating route and controller so it give us the ability to create multi level URI names

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

- Privacy validation optimizied

2017-03-27, v1.4
------------------

- Initial release
