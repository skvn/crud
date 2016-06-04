<?php namespace Skvn\Crud\Traits;

trait ModelInjectTrait {

    protected static $_preconstruct = [];
    protected static $_postconstruct = [];
    protected static $_setters = [];



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

    static function registerSetter(callable $handler)
    {
        if (!isset(static :: $_setters[static :: class]))
        {
            static :: $_setters[static :: class] = [];
        }
        static :: $_setters[static :: class][] = $handler;
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

    function callSetters($key, $value)
    {
        if (!empty(static :: $_setters[static :: class]))
        {
            foreach (static :: $_setters[static :: class] as $handler)
            {
                if ($handler($this, $key, $value) === true)
                {
                    return true;
                }
            }
        }
    }


    public static function boot()
    {
        parent::boot();
        static::bootCrud();
    }

    public static function bootCrud()
    {
        static::saved(function($instance) {

            return $instance->onAfterSave();
        });
        static::saving(function($instance)
        {
            return $instance->onBeforeSave();
        });

        static::creating(function($instance)
        {
            return $instance->onBeforeCreate();
        });

        static::created(function($instance)
        {
            return $instance->onAfterCreate();
        });

        static::deleting(function($instance)
        {
            return $instance->onBeforeDelete();
        });

        static::deleted(function($instance)
        {
            return $instance->onAfterDelete();
        });
    }



}