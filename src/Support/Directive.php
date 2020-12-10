<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Support;

abstract class Directive extends \GraphQL\Type\Definition\Directive
{
    /**
     * @return mixed
     */
    abstract public static function getInstance();

    /**
     * @param mixed $value
     * @param array<mixed> $args
     * @return mixed
     */
    abstract public function handle($value, array $args = []);
}
