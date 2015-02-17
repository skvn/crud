<?php namespace LaravelCrud\Twig;

use Twig_Extension;
use Twig_SimpleFilter;

class Acl extends Twig_Extension
{
    public function getName()
    {
        return 'LaravelCrud_Twig_Acl';
    }

    public function checkAcl($acl, $access = "")
    {
        $helper = \App :: make("CmsHelper");
        return $helper->checkAcl($acl, $access);
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('allowed', [$this, 'checkAcl']),
        ];
    }
}
