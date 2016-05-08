<?php namespace Skvn\Crud\Form;


class FieldFactory
{



    public static  function create($form, $config)
    {

        $type = 'Skvn\Crud\Form\\'.studly_case($config['type']);
        //$type = studly_case($config['type']);
        return new $type($form, $config);
    }

} 