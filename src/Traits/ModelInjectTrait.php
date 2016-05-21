<?php namespace Skvn\Crud\Traits;

trait ModelInjectTrait {

    protected static $_preconstruct = [];
    protected static $_postconstruct = [];



    static function registerPreconstruct(callable $handler)
    {
        if (!isset(static :: $_preconstruct[static :: class]))
        {
            static :: $_preconstruct[static :: class] = [];
        }
        static :: $_preconstruct[static :: class][] = $handler;
    }

    static function registerPostconstruct(callable $handler)
    {
        if (!isset(static :: $_postconstruct[static :: class]))
        {
            static :: $_postconstruct[static :: class] = [];
        }
        static :: $_postconstruct[static :: class][] = $handler;
    }

    function preconstruct()
    {
        if (!empty(static :: $_preconstruct[static :: class]))
        {
            foreach (static :: $_preconstruct[static :: class] as $handler)
            {
                $handler($this);
            }
        }
    }

    function postconstruct()
    {
        if (!empty(static :: $_postconstruct[static :: class]))
        {
            foreach (static :: $_postconstruct[static :: class] as $handler)
            {
                $handler($this);
            }
        }
    }




}