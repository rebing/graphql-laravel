<?php

declare(strict_types = 1);
namespace Rebing\GraphQL\Tests\Unit;

use GraphQL\Error\Error;
use GraphQL\Server\Helper;
use GraphQL\Server\OperationParams as BaseOperationParams;
use PHPUnit\Framework\TestCase;
use Rebing\GraphQL\Support\OperationParams;

class OperationParamsTest extends TestCase
{
    public function testGetOriginalInput(): void
    {
        $base = BaseOperationParams::create([
            'query' => '{ hello }',
            'variables' => ['foo' => 'bar'],
        ]);
        $params = new OperationParams($base);

        self::assertSame('{ hello }', $params->getOriginalInput('query'));
        self::assertSame(['foo' => 'bar'], $params->getOriginalInput('variables'));
        self::assertNull($params->getOriginalInput('nonexistent'));
    }

    public function testIsReadOnly(): void
    {
        $base = BaseOperationParams::create([
            'query' => '{ hello }',
        ], true);
        $params = new OperationParams($base);

        self::assertTrue($params->isReadOnly());

        $baseReadWrite = BaseOperationParams::create([
            'query' => '{ hello }',
        ]);
        $paramsReadWrite = new OperationParams($baseReadWrite);

        self::assertFalse($paramsReadWrite->isReadOnly());
    }

    public function testGetParsedQueryThrowsWhenNoQuery(): void
    {
        $base = BaseOperationParams::create([]);
        $params = new OperationParams($base);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('No GraphQL query available');

        $params->getParsedQuery();
    }

    public function testGetParsedQueryReturnsParsedDocument(): void
    {
        $base = BaseOperationParams::create([
            'query' => '{ hello }',
        ]);
        $params = new OperationParams($base);

        $document = $params->getParsedQuery();

        // Second call should return the same cached instance
        $document2 = $params->getParsedQuery();
        self::assertSame($document, $document2);
    }

    /**
     * When webonyx's Helper::validateOperationParams() detects invalid variables,
     * it accesses $params->originalInput['variables'] to build the error message.
     *
     * Since OperationParams::init() doesn't copy originalInput from the base,
     * the typed property (array) is uninitialized, causing a TypeError instead
     * of returning the proper validation error.
     */
    public function testValidateOperationParamsWithInvalidVariablesCrashesOnUninitializedOriginalInput(): void
    {
        $base = BaseOperationParams::create([
            'query' => '{ hello }',
            'variables' => '[1, 2]', // JSON list, not an object — triggers validation error
        ]);
        $params = new OperationParams($base);

        $helper = new Helper;

        // Should return a validation error about variables,
        // but crashes with TypeError because originalInput is uninitialized
        $this->expectException(\Error::class);
        $this->expectExceptionMessage('must not be accessed before initialization');

        $helper->validateOperationParams($params);
    }
}
