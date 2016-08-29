<?php

namespace Skvn\Crud\Twig;

use Illuminate\Foundation\Application as LaravelApplication;
use Twig_Extension;
use Twig_SimpleFilter;

class Tooltip extends Twig_Extension
{
    protected $app;

    public function __construct(LaravelApplication $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return 'Skvn\Crud_Twig_Tooltip';
    }

    public function tooltip($t, $text = '')
    {
        return str_replace('%t', $text, str_replace('%s', $t, $this->app['config']->get('crud_tooltip.pattern')));
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('tooltip', [$this, 'tooltip'], ['is_safe' => ['html']]),
        ];
    }
}
