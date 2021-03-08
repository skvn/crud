<?php

namespace Skvn\Crud\Twig;

use Illuminate\Foundation\Application as LaravelApplication;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;


class Acl extends AbstractExtension
{
    protected $app;

    public function __construct(LaravelApplication $app)
    {
        $this->app = $app;
    }

    public function getName()
    {
        return 'Skvn\Crud_Twig_Acl';
    }

    public function checkAcl($acl, $access = '')
    {
        $helper = $this->app->make('skvn.cms');

        return $helper->checkAcl($acl, $access);
    }

    public function getFilters()
    {
        return [
            new TwigFilter('allowed', [$this, 'checkAcl']),
        ];
    }
}
