<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

abstract class Directive extends \GraphQL\Type\Definition\Directive
{
    /**
     * @param mixed $value
     * @param mixed[] $args
     * @return mixed
     */
    abstract public function handle($value, array $args = []);
}
