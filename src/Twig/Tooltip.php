<?php

namespace Skvn\Crud\Twig;

use Illuminate\Foundation\Application as LaravelApplication;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class Tooltip extends AbstractExtension
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
            new TwigFilter('tooltip', [$this, 'tooltip'], ['is_safe' => ['html']]),
        ];
    }
}
