<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Support\SchemaCache;

use GraphQL\Language\Parser;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Schema;
use GraphQL\Utils\AST;
use GraphQL\Utils\BuildSchema;
use GraphQL\Utils\SchemaPrinter;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Rebing\GraphQL\GraphQL;
use Rebing\GraphQL\Support\Field;
use Rebing\GraphQL\Support\Type;
use function Safe\json_decode;
use function Safe\json_encode;

class SchemaCache
{
    protected Config $config;
    protected Cache $cache;

    /** @var array{enable: bool, cache_driver: string, cache_prefix: string} */
    protected array $cacheConfigs;

    /** @var array<string, array<string, mixed>> */
    protected array $schemaConfigs = [];

    public function __construct(Config $config, CacheFactory $cache)
    {
        $this->config = $config;
        $this->cacheConfigs = $config->get('graphql.schema_cache');
        $this->cache = $cache->store($this->cacheConfigs['cache_driver'] ?? null);
    }

    public function enabled(string $schemaName): bool
    {
        return $this->cacheConfigs['enable'] && !($this->getSchemaConfig($schemaName)['disable_cache'] ?? false);
    }

    protected function getCacheKey(string $schemaName): string
    {
        return $this->cacheConfigs['cache_prefix'] . ':' . $schemaName;
    }

    public function set(string $schemaName, Schema $schema): void
    {
        $gql = SchemaPrinter::doPrint($schema);

        $document = Parser::parse($gql);

        $json = json_encode($document->toArray());
        $mapping = $this->getSchemaConfig($schemaName);

        $this->cache->put(
            $this->getCacheKey($schemaName),
            ['json' => $json, 'mapping' => $mapping]
        );
    }

    public function get(string $schemaName): ?Schema
    {
        $data = $this->cache->get($this->getCacheKey($schemaName));

        if (!$data || !isset($data['json'], $data['mapping'])) {
            return null;
        }

        $this->schemaConfigs[$schemaName] = $data['mapping'];

        /** @var \GraphQL\Language\AST\DocumentNode $ast */
        $ast = AST::fromArray(
            json_decode($data['json'], true)
        );

        return BuildSchema::buildAST(
            $ast,
            $this->getTypeConfigDecorator($schemaName),
            ['assumeValid' => true]
        );
    }

    public function forget(string $schemaName): void
    {
        $this->cache->forget($this->getCacheKey($schemaName));
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
        return $this->schemaConfigs[$schemaName] ??= $this->enrichSchemaConfig(GraphQL::getNormalizedSchemaConfiguration($schemaName));
    }

    /**
     * @param array<string, mixed> $schemaConfig
     *
     * @return array<string, mixed>
     */
    protected function enrichSchemaConfig(array $schemaConfig): array
    {
        $namedSchemaConfig = [
            'types' => $this->config->get('graphql.types', []),
        ];

        foreach ($schemaConfig as $group => $value) {
            if ('disable_cache' === $group) {
                $namedSchemaConfig[$group] = $value;
            } elseif (\in_array($group, ['types', 'query', 'mutation', 'subscription'], true)) {
                foreach ($value as $name => $class) {
                    if (\is_int($name)) {
                        $instance = app($class);

                        if ($instance instanceof Type || $instance instanceof Field) {
                            $name = $instance->getName();
                        } elseif ($instance instanceof NamedType) {
                            $name = $instance->name();
                        } else {
                            $name = $instance->name;
                        }
                    }

                    $namedSchemaConfig[$group][$name] = $class;
                }
            }
        }

        return $namedSchemaConfig;
    }
}
