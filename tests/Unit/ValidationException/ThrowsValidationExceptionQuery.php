<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit\ValidationException;

use GraphQL\Type\Definition\Type;
use Illuminate\Support\Facades\Validator;
use Rebing\GraphQL\Support\Query;

class ThrowsValidationExceptionQuery extends Query
{
    /** @var array<string,string> */
    protected $attributes = [
        'name' => 'throwsValidationException',
    ];

    public function type(): Type
    {
        return Type::nonNull(Type::boolean());
    }

    /**
     * @param mixed $root
     * @param array<string,mixed> $args
     */
    public function resolve($root, $args): bool
    {
        Validator::make(
            $args,
            [
                'field' => 'required',
            ]
        )
            ->validate();

        return true;
    }
}
