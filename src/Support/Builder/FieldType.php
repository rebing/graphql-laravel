<?php
declare(strict_types = 1);
namespace Rebing\GraphQL\Support\Builder;

use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use Illuminate\Support\Fluent;
use Rebing\GraphQL\Exception\TypeNotDefine;
use Rebing\GraphQL\Support\Facades\GraphQL;
use RuntimeException;

/**
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $alias
 * @property array $args
 * @property array $rules
 * @property bool $selectable
 * @property bool $isRelation
 * @property mixed $default
 * @property Closure $resolve
 * @property Closure $query
 * @property Closure|string $privacy
 * @property string $deprecationReason
 * @property array $always
 * @property Type $type
 */
class FieldType extends Fluent
{
    public const ALIAS = 'alias';
    public const ALWAYS = 'always';
    public const ARGS = 'args';
    public const DEFAULT = 'default';
    public const DEPRECATION_REASON = 'deprecationReason';
    public const DESCRIPTION = 'description';
    public const IS_RELATION = 'is_relation';
    public const NAME = 'name';
    public const PRIVACY = 'privacy';
    public const QUERY = 'query';
    public const RESOLVE = 'resolve';
    public const RULES = 'rules';
    public const SELECTABLE = 'selectable';
    public const TYPE = 'type';
    public const TYPE_NAME = 'typeName';

    public static function make(string $name = ''): static
    {
        return new static([
            self::NAME => $name,
        ]);
    }

    public function typeName(string $value): static
    {
        $this->offsetSet(self::TYPE_NAME, $value);

        return $this;
    }

    public function name(string $value): static
    {
        $this->offsetSet(self::NAME, $value);

        return $this;
    }

    public function description(string $value): static
    {
        $this->offsetSet(self::DESCRIPTION, $value);

        return $this;
    }

    public function alias(string $value): static
    {
        $this->offsetSet(self::ALIAS, $value);

        return $this;
    }

    public function args(array $value): static
    {
        $this->offsetSet(self::ARGS, $value);

        return $this;
    }

    public function rules(array $value): static
    {
        $this->offsetSet(self::RULES, $value);

        return $this;
    }

    public function selectable(bool $flag = true): static
    {
        $this->offsetSet(self::SELECTABLE, $flag);

        return $this;
    }

    public function isRelation(bool $flag = true): static
    {
        $this->offsetSet(self::IS_RELATION, $flag);

        return $this;
    }

    public function default($value): static
    {
        $this->offsetSet(self::DEFAULT, $value);

        return $this;
    }

    public function resolve(Closure $callable): static
    {
        $this->offsetSet(self::RESOLVE, $callable);

        return $this;
    }

    public function query(Closure $callable): static
    {
        $this->offsetSet(self::QUERY, $callable);

        return $this;
    }

    public function privacy(Closure|string $callable): static
    {
        $this->offsetSet(self::PRIVACY, $callable);

        return $this;
    }

    public function deprecationReason(string $value): static
    {
        $this->offsetSet(self::DEPRECATION_REASON, $value);

        return $this;
    }

    public function always(array $value): static
    {
        $this->offsetSet(self::ALWAYS, $value);

        return $this;
    }

    public function type(Type $value): static
    {
        $this->offsetSet(self::TYPE, $value);

        return $this;
    }

    public function graphQlType(string $value, $fresh = false): static
    {
        $this->checkType();
        $this->offsetSet(self::TYPE, GraphQL::type($value, $fresh));

        return $this;
    }

    public function int(): static
    {
        $this->checkType();
        $this->offsetSet(self::TYPE, Type::int());

        return $this;
    }

    public function string(): static
    {
        $this->checkType();
        $this->offsetSet(self::TYPE, Type::string());

        return $this;
    }

    public function boolean(): static
    {
        $this->checkType();
        $this->offsetSet(self::TYPE, Type::boolean());

        return $this;
    }

    public function id(): static
    {
        $this->checkType();
        $this->offsetSet(self::TYPE, Type::id());

        return $this;
    }

    public function listOf($type): static
    {
        $this->checkType();
        $this->offsetSet(self::TYPE, Type::listOf($type));

        return $this;
    }

    public function nonNull($type): static
    {
        $this->checkType();
        $this->offsetSet(self::TYPE, Type::nonNull($type));

        return $this;
    }

    public function toArray(): array
    {
        $this->validateField(self::NAME);
        $this->validateField(self::TYPE);

        return parent::toArray();
    }

    private function checkType(): void
    {
        if (!$this->get(self::TYPE) instanceof ScalarType) {
            return;
        }

        new RuntimeException('field Type define, you can\'t change it');
    }

    private function validateField($field): void
    {
        if (null !== $this->get($field) && '' !== $this->get($field)) {
            return;
        }

        throw new TypeNotDefine(
            sprintf(
                'Field `%s` not define on %s %s',
                $field,
                $this->get(self::TYPE_NAME),
                null !== $this->get(self::DESCRIPTION) ? "| description: {$this->get(self::DESCRIPTION)}" : ''
            )
        );
    }
}
