<?php namespace LaravelCrud\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

class Tooltip extends Twig_Extension
{
    public function getName()
    {
        return 'LaravelCrud_Twig_Tooltip';
    }

    public function tooltip($t, $text = "")
    {
        return str_replace("%t", $text, str_replace('%s', $t, \Config :: get('crud_tooltip.pattern')));
    }


    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('tooltip', [$this, 'tooltip'], ['is_safe' => ['html']]),
        ];
    }
}
