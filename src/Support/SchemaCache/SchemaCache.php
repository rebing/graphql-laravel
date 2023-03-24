<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\SchemaCache;

use GraphQL\Language\Parser;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Rebing\GraphQL\GraphQL;
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

    protected function getTypeConfigDecorator(string $schemaName): callable
    {
        return new TypeConfigDecorator($this->getSchemaConfig($schemaName));
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSchemaConfig(string $schemaName): array
    {
        return $this->schemaConfigs[$schemaName] ??= GraphQL::getNormalizedSchemaConfiguration($schemaName);
    }
}
