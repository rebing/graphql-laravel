<?php

namespace Rebing\GraphQL\Support;

abstract class Privacy {

    public function fire()
    {
        return $this->validate(func_get_args()[0]);
    }

    public abstract function validate(array $args);

}