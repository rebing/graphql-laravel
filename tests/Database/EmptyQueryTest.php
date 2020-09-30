<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Database;

use Rebing\GraphQL\Tests\Support\Traits\SqlAssertionTrait;
use Rebing\GraphQL\Tests\TestCaseDatabase;

class EmptyQueryTest extends TestCaseDatabase
{
    use SqlAssertionTrait;

    /**
     * @param string $query
     * @testWith    [""]
     *              [" "]
     *              ["#"]
     */
    public function testEmptyQuery(string $query): void
    {
        $this->sqlCounterReset();

        $result = $this->httpGraphql($query);

        $this->assertCount(1, $result['errors']);
        $this->assertSame('Syntax Error: Unexpected <EOF>', $result['errors'][0]['message']);
        $this->assertSame('graphql', $result['errors'][0]['extensions']['category']);
    }
}
