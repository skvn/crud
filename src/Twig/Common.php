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
            return '/vendor/' . $package . '/' . $asset;
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


            return $path;
        }
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('asset', [$this, 'asset']),
        ];
    }
}
