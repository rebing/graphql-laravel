<?php

namespace Rebing\GraphQL\Support;

class Mutation extends Field {

    protected function rules(array $args = [])
    {
        return [];
    }

    public function getRules()
    {
        $arguments = func_get_args();

        $rules = call_user_func_array([$this, 'rules'], $arguments);
        $argsRules = [];
        foreach($this->args() as $name => $arg)
        {
            if(isset($arg['rules']))
            {
                if(is_callable($arg['rules']))
                {
                    $argsRules[$name] = call_user_func_array($arg['rules'], $arguments);
                }
                else
                {
                    $argsRules[$name] = $arg['rules'];
                }
            }
        }

        return array_merge($argsRules, $rules);
    }

}
