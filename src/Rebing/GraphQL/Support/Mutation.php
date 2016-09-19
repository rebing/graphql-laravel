<?php

namespace Rebing\GraphQL\Support;

use Rebing\GraphQL\Error\ValidationError;
use Validator;

class Mutation extends Field {
    
    protected function rules()
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
    
    protected function getResolver()
    {
        if(!method_exists($this, 'resolve'))
        {
            return null;
        }
        
        $resolver = array($this, 'resolve');
        return function() use ($resolver)
        {
            $arguments = func_get_args();
            $rules = call_user_func_array([$this, 'getRules'], $arguments);
            if(sizeof($rules))
            {
                $args = array_get($arguments, 1, []);
                $validator = Validator::make($args, $rules);
                if($validator->fails()) 
                {
                    throw with(new ValidationError('validation'))->setValidator($validator);
                }
            }
            return call_user_func_array($resolver, $arguments);
        };
    }
    
}
