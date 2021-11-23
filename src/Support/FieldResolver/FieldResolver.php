<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\FieldResolver;

use GraphQL\Executor\Executor;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;

class FieldResolver extends Executor
{
    /** @var \Rebing\GraphQL\Support\FieldResolver\DirectiveHandler */
    private $directiveHandler;

    public function __construct(DirectiveHandler $directiveHandler)
    {
        $this->directiveHandler = $directiveHandler;
    }

    public function setSchema(Schema $schema): self
    {
        $this->directiveHandler->loadDirectivesBySchema($schema);

        return $this;
    }

    public function __invoke($objectValue, $args, $contextValue, ResolveInfo $info)
    {
        $property = self::defaultFieldResolver($objectValue, $args, $contextValue, $info);

        return $this->directiveHandler->applyDirectives($property, $info);
    }
}
