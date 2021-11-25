<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\FieldResolver;

use GraphQL\Executor\Values;
use GraphQL\Language\AST\DirectiveNode;
use GraphQL\Type\Definition\Directive;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Schema;

class DirectiveHandler
{
    /** @var array<\GraphQL\Type\Definition\Directive> */
    private $directives;

    public function loadDirectivesBySchema(Schema $schema): self
    {
        $this->directives = [];

        foreach ($schema->getDirectives() as $directive) {
            $this->directives[$directive->name] = $directive;
        }

        return $this;
    }

    public function getDirective(string $name): ?Directive
    {
        return $this->directives[$name] ?? null;
    }

    /**
     * @param mixed $property
     *
     * @return mixed
     */
    public function applyDirectives($property, ResolveInfo $info)
    {
        $fieldNode = $info->fieldNodes[0];

        foreach ($fieldNode->directives as $directiveNode) {
            $directive = $this->getDirective($directiveNode->name->value);

            if ($directive instanceof \Rebing\GraphQL\Support\Directive) {
                $property = $directive->handle(
                    $property,
                    $this->getDirectiveArguments($directiveNode, $directive, $info->variableValues)
                );
            }
        }

        return $property;
    }

    /**
     * @param array<string, mixed> $variableValues
     *
     * @return array<string, mixed>
     */
    protected function getDirectiveArguments(DirectiveNode $directiveNode, Directive $directive, array $variableValues): array
    {
        return Values::getArgumentValues(
            $directive,
            $directiveNode,
            $variableValues
        );
    }
}
