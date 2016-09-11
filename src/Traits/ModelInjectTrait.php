<?php

namespace Skvn\Crud\Traits;

trait ModelInjectTrait
{
    protected static $_preconstruct = [];
    protected static $_postconstruct = [];
    protected static $_setters = [];

    public static function registerPreconstruct(callable $handler)
    {
        if (! isset(static :: $_preconstruct[static :: class])) {
            static :: $_preconstruct[static :: class] = [];
        }
        static :: $_preconstruct[static :: class][] = $handler;
    }

    public static function registerPostconstruct(callable $handler)
    {
        if (! isset(static :: $_postconstruct[static :: class])) {
            static :: $_postconstruct[static :: class] = [];
        }
        static :: $_postconstruct[static :: class][] = $handler;
    }

    public static function registerSetter(callable $handler)
    {
        if (! isset(static :: $_setters[static :: class])) {
            static :: $_setters[static :: class] = [];
        }
        static :: $_setters[static :: class][] = $handler;
    }

    public function preconstruct()
    {
        if (! empty(static :: $_preconstruct[static :: class])) {
            foreach (static :: $_preconstruct[static :: class] as $handler) {
                $handler($this);
            }
        }
    }

    public function postconstruct()
    {
        if (! empty(static :: $_postconstruct[static :: class])) {
            foreach (static :: $_postconstruct[static :: class] as $handler) {
                $handler($this);
            }
        }
    }

    public function callSetters($key, $value)
    {
        if (! empty(static :: $_setters[static :: class])) {
            foreach (static :: $_setters[static :: class] as $handler) {
                if ($handler($this, $key, $value) === true) {
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
        static::saved(function ($instance) {
//            $instance->crudRelations->save();
            return $instance->onAfterSave();
        });
        static::saving(function ($instance) {
            //if ($instance->validate()) {
                $instance->crudHandleTrackAuthors('update');

                return $instance->onBeforeSave();
            //}

            return false;
        });

        static::creating(function ($instance) {
            $instance->crudHandleTrackAuthors('create');

            return $instance->onBeforeCreate();
        });

        static::created(function ($instance) {
            return $instance->onAfterCreate();
        });

        static::deleting(function ($instance) {
            $check = $instance->onBeforeDelete();
            if ($check !== false) {
                $instance->crudRelations->delete();
            }

            return $check;
        });

        static::deleted(function ($instance) {
            return $instance->onAfterDelete();
        });
    }

    protected function onBeforeCreate()
    {
        return true;
    }

    protected function onAfterCreate()
    {
        return true;
    }

    protected function onBeforeSave()
    {
        return true;
    }

    protected function onAfterSave()
    {
        return true;
    }

    protected function onBeforeDelete()
    {
        return true;
    }

    protected function onAfterDelete()
    {
        return true;
    }

    protected function crudHandleTrackAuthors($op)
    {
        if ($this->trackAuthors && $this->app['auth']->check()) {
            $const = ($op == 'create') ? 'static::CREATED_BY' : 'static::UPDATED_BY';
            $fld = ($op == 'create') ? 'created_by' : 'updated_by';
            $prop = defined($const) ? constant($const) : $fld;
            $this->$prop = $this->app['auth']->user()->id;
        }
    }
}
