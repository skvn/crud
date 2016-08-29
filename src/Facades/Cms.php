<?php

namespace Skvn\Crud\Facades;

use Illuminate\Support\Facades\Facade;

class Cms extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'skvn.cms';
    }
}
