<?php

declare(strict_types=1);

namespace Rebing\GraphQL\Tests\Unit\CustomContext;

use Illuminate\Http\Request;

class Context
{
    /** @var Request */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
