<?php

namespace Rebing\GraphQL\Support;

class Privacy {

    public function validate()
    {
        return function(array $args)
        {
            return true;
        };
    }

}