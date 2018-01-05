<?php

namespace Rebing\GraphQL\Support;

use Rebing\GraphQL\Error\AuthorizationError;
use Validator;
use Illuminate\Support\Fluent;
use Rebing\GraphQL\Error\ValidationError;

class Field extends Fluent {

    /**
     * Override this in your queries or mutations
     * to provide custom authorization
     */
    public function authorize(array $args)
    {
        return true;
    }

    public function attributes()
    {
        return [];
    }

    public function type()
    {
        return null;
    }

    public function args()
    {
        return [];
    }

    protected function getResolver()
    {
        if(!method_exists($this, 'resolve'))
        {
            return null;
        }

        $resolver = [$this, 'resolve'];
        $authorize = [$this, 'authorize'];
        return function() use ($resolver, $authorize)
        {
            $arguments = func_get_args();

            // Get all given arguments
            if( ! is_null($arguments[2]) && is_array($arguments[2]))
            {
                $arguments[1] = array_merge($arguments[1], $arguments[2]);
            }

            // Authorize
            if(call_user_func($authorize, $arguments[1]) != true)
            {
                throw with(new AuthorizationError('Unauthorized'));
            }

            // Validate mutation arguments
            if(method_exists($this, 'getRules'))
            {
                $args = array_get($arguments, 1, []);
                $rules = call_user_func_array([$this, 'getRules'], [$args]);
                if(sizeof($rules))
                {
                    $validator = Validator::make($args, $rules);
                    if($validator->fails())
                    {
                        throw with(new ValidationError('validation'))->setValidator($validator);
                    }
                }
            }

            // Replace the context argument with 'selects and relations'
            // $arguments[1] is direct args given with the query
            // $arguments[2] is context (params given with the query)
            // $arguments[3] is ResolveInfo
            if(isset($arguments[3]))
            {
                $fields = new SelectFields($arguments[3], $this->type(), $arguments[1]);
                $arguments[2] = $fields;
            }

            return call_user_func_array($resolver, $arguments);
        };
    }

    /**
     * Get the attributes from the container.
     *
     * @return array
     */
    public function getAttributes()
    {
        $attributes = $this->attributes();

        $attributes = array_merge($this->attributes, [
            'args' => $this->args()
        ], $attributes);

        $type = $this->type();
        if(isset($type))
        {
            $attributes['type'] = $type;
        }

        $resolver = $this->getResolver();
        if(isset($resolver))
        {
            $attributes['resolve'] = $resolver;
        }

        return $attributes;
    }

    /**
     * Convert the Fluent instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->getAttributes();
    }

    /**
     * Dynamically retrieve the value of an attribute.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]) ? $attributes[$key]:null;
    }

    /**
     * Dynamically check if an attribute is set.
     *
     * @param  string  $key
     * @return void
     */
    public function __isset($key)
    {
        $attributes = $this->getAttributes();
        return isset($attributes[$key]);
    }

}
