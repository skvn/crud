<?php namespace LaravelCrud\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

class Common extends Twig_Extension
{
    public function getName()
    {
        return 'LaravelCrud_Twig_Common';
    }

    function asset($asset, $use_skin=0, $package = 'crud')
    {
        if (!$use_skin)
        {
            return '/vendor/' . $package . '/' . $asset . '?s=' . \Config :: get('app.serial');
        }
        else
        {
            $path = '/skins/';
            $path  .= \Config::get('view.skin').'/';

            if (!empty($package))
            {
                $path .= 'vendor/'.$package.'/';
            }

            $path .= $asset;
            $path .= '?s=' . \Config :: get('app.serial');


            return $path;
        }
    }

    function modelView($view, $model)
    {
        return \App :: make('CrudHelper')->resolveModelView($model, $view);
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('asset', [$this, 'asset']),
            new Twig_SimpleFilter('model_view', [$this, 'modelView'])
        ];
    }
}
