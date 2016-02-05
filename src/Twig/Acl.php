<?php namespace Skvn\Crud\Twig;

use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use Illuminate\Foundation\Application as LaravelApplication;

class Acl extends Twig_Extension
{
    protected $app;

    function __construct(LaravelApplication $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return 'Skvn\Crud_Twig_Acl';
    }

    public function checkAcl($acl, $access = "")
    {
        $helper = $this->app->make("skvn.cms");
        return $helper->checkAcl($acl, $access);
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('allowed', [$this, 'checkAcl']),
        ];
    }



}
