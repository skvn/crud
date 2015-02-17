<?php

namespace LaravelCrud\Filter;


class FilterFactory
{



    public static  function create(array $context_params)
    {
        static  $instances;
        $context = implode(':',$context_params);
        if (empty($instances[$context]))
        {

            $instances[$context] =  new Filter($context_params);
        }

        return $instances[$context];
    }

} 