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
            if ($instance->eventsDisabled)
            {
                return true;
            }
            $instance->crudRelations->save();
            return $instance->onAfterSave();
        });
        static::saving(function($instance)
        {
            if ($instance->eventsDisabled)
            {
                return true;
            }
            if ($instance->validate())
            {
                $instance->crudHandleTrackAuthors("update");
                return $instance->onBeforeSave();
            }
            return false;
        });

        static::creating(function($instance)
        {
            if ($instance->eventsDisabled)
            {
                return true;
            }
            $instance->crudHandleTrackAuthors("create");
            return $instance->onBeforeCreate();
        });

        static::created(function($instance)
        {
            if ($instance->eventsDisabled)
            {
                return true;
            }
            return $instance->onAfterCreate();
        });

        static::deleting(function($instance)
        {
            if ($instance->eventsDisabled)
            {
                return true;
            }
            $check = $instance->onBeforeDelete();
            if ($check !== false)
            {
                $instance->crudRelations->delete();
            }
            return $check;
        });

        static::deleted(function($instance)
        {
            if ($instance->eventsDisabled)
            {
                return true;
            }
            return $instance->onAfterDelete();
        });
    }

    protected  function onBeforeCreate()
    {
        return true;
    }


    protected  function onAfterCreate()
    {
        return true;
    }

    protected  function onBeforeSave()
    {
        return true;
    }

    protected  function onAfterSave()
    {
        return true;
    }

    protected  function onBeforeDelete()
    {
        return true;
    }

    protected  function onAfterDelete()
    {
        return true;
    }


    protected function crudHandleTrackAuthors($op)
    {
        $const = ($op == "create") ? "static::CREATED_BY" : "static::UPDATED_BY";
        $fld = ($op == "create") ? "created_by" : "updated_by";
        $prop = defined($const) ? constant($const) : $fld;
        if ($this->trackAuthors && $this->app['auth']->check())
        {
            $this->$prop = $this->app['auth']->user()->id;
        }

    }


}