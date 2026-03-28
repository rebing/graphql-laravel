<?php

declare(strict_types = 1);

use Auth;

trait Authenticate
{
    public function authorize($root, array $args, $ctx): bool
    {
        return !Auth::guest();
    }
}
