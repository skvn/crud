<?php

namespace LaravelCrud\Wizard;



class CrudWizard
{


    static public  function  saveModel(\Illuminate\Foundation\Application $app)
    {

        $app['view']->addNamespace('crud_wizard', __DIR__.'/../stubs/views');
        $v = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $app['view']->make('crud_wizard::crud_config', ['model'=>$app['request']->all()])->render());
        print_r($app['request']->all());
        print_r($v);
        exit;
    }

}