<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

use Closure;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type as GraphqlType;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use Rebing\GraphQL\Error\AuthorizationError;
use Rebing\GraphQL\Error\ValidationError;
use Rebing\GraphQL\Support\AliasArguments\AliasArguments;
use ReflectionMethod;

/**
 * @property string $name
 */
abstract class Field
{
    /**
     * The depth the SelectField and ResolveInfoFieldsAndArguments classes traverse.
     *
     * @var int
     */
    protected $depth = 5;

    protected $attributes = [];

    /**
     * Override this in your queries or mutations
     * to provide custom authorization.
     *
     * @param  mixed  $root
     * @param  array  $args
     * @param  mixed  $ctx
     * @param  ResolveInfo|null  $resolveInfo
     * @param  Closure|null  $getSelectFields
     * @return bool
     */
    public function authorize($root, array $args, $ctx, ResolveInfo $resolveInfo = null, Closure $getSelectFields = null): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [];
    }

    abstract public function type(): GraphqlType;

    /**
     * @return array<string,array>
     */
    public function args(): array
    {
        return [];
    }

    /**
     * Define custom Laravel Validator messages as per Laravel 'custom error messages'.
     *
     * @param array $args submitted arguments
     *
     * @return array
     */
    public function validationErrorMessages(array $args = []): array
    {
        return [];
    }

    /**
     * @param array<string,mixed> $args
     * @return array<string,mixed>
     */
    protected function rules(array $args = []): array
    {
        return [];
    }

    /**
     * @param array<string,mixed> $arguments
     * @return array<string,mixed>
     */
    public function getRules(array $arguments = []): array
    {
        $rules = $this->rules($arguments);
        $argsRules = (new Rules($this->args(), $arguments))->get();

        return array_merge($argsRules, $rules);
    }

    /**
     * @param array<string,mixed> $fieldsAndArgumentsSelection
     * @return void
     */
    public function validateFieldArguments(array $fieldsAndArgumentsSelection): void
    {
        $argsRules = (new RulesInFields($this->type(), $fieldsAndArgumentsSelection))->get();
        if (count($argsRules)) {
            $validator = $this->getValidator($fieldsAndArgumentsSelection, $argsRules);
            if ($validator->fails()) {
                throw new ValidationError('validation', $validator);
            }
        }
    }

    public function getValidator(array $args, array $rules): ValidatorContract
    {
        // allow our error messages to be customised
        $messages = $this->validationErrorMessages($args);

        return Validator::make($args, $rules, $messages);
    }

    protected function getResolver(): ?Closure
    {
        if (! method_exists($this, 'resolve')) {
            return null;
        }

        $resolver = [$this, 'resolve'];
        $authorize = [$this, 'authorize'];

        return function () use ($resolver, $authorize) {
            // 0 - the "root" object; `null` for queries, otherwise the parent of a type
            // 1 - the provided `args` of the query or type (if applicable), empty array otherwise
            // 2 - the "GraphQL query context" (see \Rebing\GraphQL\GraphQLController::queryContext)
            // 3 - \GraphQL\Type\Definition\ResolveInfo as provided by the underlying GraphQL PHP library
            // 4 (!) - added by this library, encapsulates creating a `SelectFields` instance
            $arguments = func_get_args();

            // Validate mutation arguments
            $args = $arguments[1];

            $rules = $this->getRules($args);

            if (count($rules)) {
                $validator = $this->getValidator($args, $rules);
                if ($validator->fails()) {
                    throw new ValidationError('validation', $validator);
                }
            }

            $fieldsAndArguments = (new ResolveInfoFieldsAndArguments($arguments[3]))->getFieldsAndArgumentsSelection($this->depth);

            // Validate arguments in fields
            $this->validateFieldArguments($fieldsAndArguments);

            $arguments[1] = $this->getArgs($arguments);

            // Authorize
            if (true != call_user_func_array($authorize, $arguments)) {
                throw new AuthorizationError($this->getAuthorizationMessage());
            }

            $method = new ReflectionMethod($this, 'resolve');

            $additionalParams = array_slice($method->getParameters(), 3);

            $additionalArguments = array_map(function ($param) use ($arguments, $fieldsAndArguments) {
                $className = null !== $param->getClass() ? $param->getClass()->getName() : null;

                if (null === $className) {
                    throw new InvalidArgumentException("'{$param->name}' could not be injected");
                }

                if (Closure::class === $param->getType()->getName()) {
                    return function (int $depth = null) use ($arguments, $fieldsAndArguments): SelectFields {
                        return $this->instanciateSelectFields($arguments, $fieldsAndArguments, $depth);
                    };
                }

                if (SelectFields::class === $className) {
                    return $this->instanciateSelectFields($arguments, $fieldsAndArguments, null);
                }

                if (ResolveInfo::class === $className) {
                    return $arguments[3];
                }

                return app()->make($className);
            }, $additionalParams);

            return call_user_func_array($resolver, array_merge(
                [$arguments[0], $arguments[1], $arguments[2]],
                $additionalArguments
            ));
        };
    }

    /**
     * @param array<int,mixed> $arguments
     * @param int $depth
     * @param array<string,mixed> $fieldsAndArguments
     * @return SelectFields
     */
    private function instanciateSelectFields(array $arguments, array $fieldsAndArguments, int $depth = null): SelectFields
    {
        $ctx = $arguments[2] ?? null;

        if ($depth !== null && $depth !== $this->depth) {
            $fieldsAndArguments = (new ResolveInfoFieldsAndArguments($arguments[3]))
                ->getFieldsAndArgumentsSelection($depth);
        }

        return new SelectFields($this->type(), $arguments[1], $ctx, $fieldsAndArguments);
    }

    protected function aliasArgs(array $arguments): array
    {
        return (new AliasArguments($this->args(), $arguments[1]))->get();
    }

    protected function getArgs(array $arguments): array
    {
        return $this->aliasArgs($arguments);
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        $attributes = $this->attributes();

        $attributes = array_merge(
            $this->attributes,
            ['args' => $this->args()],
            $attributes
        );

        $attributes['type'] = $this->type();

        $resolver = $this->getResolver();
        if (isset($resolver)) {
            $attributes['resolve'] = $resolver;
        }

        return $attributes;
    }

    public function getAuthorizationMessage(): string
    {
        return 'Unauthorized';
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();

        return $attributes[$key] ?? null;
    }

    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }
}
