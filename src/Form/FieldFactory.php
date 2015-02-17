<?php

namespace LaravelCrud\Form;


class FieldFactory
{



    public static  function create($form, $config)
    {

        $type = 'LaravelCrud\Form\\'.studly_case($config['type']);
        return new $type($form, $config);
    }

} 