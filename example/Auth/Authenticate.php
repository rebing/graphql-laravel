<?php

use Auth;

trait Authenticate {

    public function authorize(array $args)
    {
        return ! Auth::guest();
    }

}