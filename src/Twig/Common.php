<?php namespace Skvn\Crud\Twig;

use Twig_Extension;
use Twig_SimpleFilter;
use Illuminate\Foundation\Application as LaravelApplication;

class Common extends Twig_Extension
{
    protected $app;

    function __construct(LaravelApplication $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return 'Skvn\Crud_Twig_Common';
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

    public function readableFilesize($size)
    {
        if( $size <= 0 ) {
            return '0 KB';
        }

        if( $size === 1 ) {
            return '1 byte';
        }

        $mod = 1024;
        $units = array('bytes', 'KB', 'MB', 'GB', 'TB', 'PB');

        for( $i = 0; $size > $mod && $i < count($units) - 1; ++$i ) {
            $size /= $mod;
        }

        return round($size, 2) . ' ' . $units[$i];
    }


    function modelView($view, $model)
    {
        return $this->app->make('skvn.crud')->resolveModelView($model, $view);
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('asset', [$this, 'asset']),
            new Twig_SimpleFilter('readable_filesize', [$this, 'readableFilesize']),
            new Twig_SimpleFilter('model_view', [$this, 'modelView'])
        ];
    }
}
