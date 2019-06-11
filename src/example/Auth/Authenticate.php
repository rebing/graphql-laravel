<?php

declare(strict_types=1);

use Auth;

trait Authenticate
{
    public function authorize(array $args)
    {
        return ! Auth::guest();
    }
}
