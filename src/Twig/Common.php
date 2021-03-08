<?php

namespace Skvn\Crud\Twig;

use Illuminate\Foundation\Application as LaravelApplication;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class Common extends AbstractExtension
{
    protected $app;

    public function __construct(LaravelApplication $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return 'Skvn\Crud_Twig_Common';
    }

    public function asset($asset, $use_skin = 0, $package = 'crud')
    {
        if (strpos($asset, '/') === 0) {
            return $asset.'?s='.$this->app['config']->get('app.serial');
        }
        if (! $use_skin) {
            return '/vendor/'.$package.'/'.$asset.'?s='.$this->app['config']->get('app.serial');
        } else {
            $path = '/skins/';
            $path .= $this->app['config']->get('view.skin').'/';

            if (! empty($package)) {
                $path .= 'vendor/'.$package.'/';
            }

            $path .= $asset;
            $path .= '?s='.$this->app['config']->get('app.serial');


            return $path;
        }
    }

    public function isNumeric($val)
    {
        return is_numeric($val);
    }

    public function arrayValue($val)
    {
        if (is_numeric($val) || is_bool($val)) {
            return $val;
        } else {
            return '"'.$val.'"';
        }
    }

    public function readableFilesize($size)
    {
        if ($size <= 0) {
            return '0 KB';
        }

        if ($size === 1) {
            return '1 byte';
        }

        $mod = 1024;
        $units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $size > $mod && $i < count($units) - 1; ++$i) {
            $size /= $mod;
        }

        return round($size, 2).' '.$units[$i];
    }

    public function modelView($view, $model)
    {
        return $model->resolveView($view);
    }

    public function absoluteUrl($url)
    {
        return $this->app['config']->get('app.url').$url;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('asset', [$this, 'asset']),
            new TwigFilter('readable_filesize', [$this, 'readableFilesize']),
            new TwigFilter('model_view', [$this, 'modelView']),
            new TwigFilter('is_numeric', [$this, 'isNumeric']),
            new TwigFilter('array_value', [$this, 'arrayValue']),
            new TwigFilter('abs_url', [$this, 'absoluteUrl']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('snake_case', '\\Illuminate\\Support\\Str::snake'),
            new TwigFunction('camel_case', '\\Illuminate\\Support\\Str::camel'),
            new TwigFunction('studly_case', '\\Illuminate\\Support\\Str::studly'),
            new TwigFunction('crud_dump', function ($v) {
                return '<pre>'.print_r($v, true).'</pre>';
            }, ['is_safe' => ['html']]),

        ];
    }
}
