<?php

declare(strict_types = 1);
namespace Rebing\GraphQL;

use Closure;
use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\json_decode;
use function Safe\json_encode;
use function Safe\unlink;

class SchemaCache
{
    protected Application $app;
    protected Cache $cache;
    protected Config $config;
    /** @var array<string, array<string, mixed>> */
    protected array $schemaConfigs = [];

    public function __construct(Application $app, Cache $cache, Config $config)
    {
        $this->app = $app;
        $this->cache = $cache;
        $this->config = $config;
    }

    public function enabled(string $schemaName): bool
    {
        return $this->getSchemaConfig($schemaName)['cache'] ?? false;
    }

    public function set(string $schemaName, Schema $schema): void
    {
        $gql = SchemaPrinter::doPrint($schema);

        $document = Parser::parse($gql);

        file_put_contents(
            $this->getSchemaCachePath($schemaName),
            json_encode($document->toArray())
        );
    }

    public function get(string $schemaName): ?Schema
    {
        if (!file_exists($path = $this->getSchemaCachePath($schemaName))) {
            return null;
        }

        /** @var \GraphQL\Language\AST\DocumentNode $ast */
        $ast = AST::fromArray(
            json_decode(file_get_contents($path), true)
        );

        return BuildSchema::buildAST(
            $ast,
            $this->getTypeConfigDecorator($schemaName),
            ['assumeValid' => true]
        );
    }

    public function flush(string $schemaName): void
    {
        if (file_exists($path = $this->getSchemaCachePath($schemaName))) {
            unlink($path);
        }
    }

    protected function getSchemaCachePath(string $schemaName): string
    {
        return $this->app->storagePath() . "/app/schema-cache-$schemaName.json";
    }

    protected function getTypeConfigDecorator(string $schemaName): Closure
    {
        $classMapping = $this->getClassMapping($schemaName);

        return static function (array $config) use ($classMapping) {
            $name = strtolower($config['name']);

            if (\in_array($name, ['query', 'mutation'], true)) {
                $config['fields'] = static function () use ($config, $name, $classMapping) {
                    $fields = $config['fields']();

                    foreach ($fields as &$field) {
                        $className = $classMapping[$name][$field['astNode']->name->value];

                        $field['resolve'] = static function ($root, ...$arguments) use ($className) {
                            /** @var \Rebing\GraphQL\Support\Field $instance */
                            $instance = app($className);

                            $resolver = $instance->getResolver();

                            return $resolver ? $resolver($root, ...$arguments) : null;
                        };
                    }

                    return $fields;
                };
            }

            return $config;
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSchemaConfig(string $schemaName): array
    {
        return $this->schemaConfigs[$schemaName] ??= GraphQL::getNormalizedSchemaConfiguration($schemaName);
    }

    /**
     * @return array<string, array<class-string>>
     */
    protected function getClassMapping(string $schemaName): array
    {
        return array_merge_recursive(
            $this->getSchemaConfig($schemaName),
            ['types' => $this->config->get('graphql.types', [])] // add global types
        );
    }
}
