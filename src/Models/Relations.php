<?php namespace Skvn\Crud\Models;

use Illuminate\Support\Str;
use ArrayAccess;
use Skvn\Crud\Exceptions\ConfigException;

class Relations implements ArrayAccess
{
    protected $model;
    protected $relations = [];

    function __construct(CrudModel $model)
    {
        $this->model = $model;
    }

    function has($name)
    {
        return array_key_exists("relation", $this->model->getField($this->stripName($name)[0]));
    }

    protected function stripName($name)
    {
        if (strpos($name, "_") !== false)
        {
            $split = explode('_', $name);
            if (count($split) == 2 && in_array($split[1], ['ids', 'first', 'count']))
            {
                return $split;
            }
        }
        return [$name, null];
    }

    function defined($name)
    {
        return isset($this->relations[$name]);
    }

    function define($name)
    {
        if (!$this->defined($name))
        {
            $info = $this->model->getField($name, true);
            $class = $this->model->getApp()['config']->get('crud_common.relations')[$info['relation']] ?? null;
            if (empty($class))
            {
                throw new ConfigException("Unknown relation " . $info['relation'] . ' on model ' . get_class($this->model));
            }
            $this->relations[$name] = new $class($this->model, $info);
            $this->relations[$name]->create();
        }
        return $this->relations[$name];
    }

    function undef($name)
    {
        if ($this->defined($name))
        {
            unset($this->relations[$name]);
        }
    }

    function undefAll()
    {
        $this->model->setRelations([]);
        foreach ($this->relations as $r => $rel)
        {
            $this->undef($r);
        }
    }

    function getRelation($name)
    {
        return $this->define($name)->getRelation();
    }

    function get($name)
    {
        return $this->define($name)->get();
    }

    function getAny($name)
    {
        $rel = $this->stripName($name);
        switch ($rel[1])
        {
            case "ids":
                return $this->getIds($rel[0]);
            case "first":
                return $this->get($rel[0])->first();
            case "count":
                return $this->get($rel[0])->count();
            default:
                return $this->get($rel[0]);

        }
    }

    function set($name, $value)
    {
        $this->define($name)->set($value);
        return $this;
    }

    function save()
    {
        foreach ($this->relations as $relation)
        {
            if ($relation->isDirty())
            {
                $relation->save();
                $relation->resetDirty();
            }
        }
        return true;
    }

    function delete()
    {
        foreach ($this->model->confParam('fields') as $name => $field)
        {
            if (!empty($field['relation']))
            {
                $this->define($name)->delete();
            }
        }
    }

    function resolveReference($ref)
    {
        if (strpos($ref, '::') !== false)
        {
            list($rel, $attr) = explode("::", $ref);
            return ['rel' => $rel, 'attr' => $attr];
        }
        return false;
    }

    function getIds($name)
    {
        return $this->define($name)->getIds();
    }

    function isMany($name)
    {
        return $this->has($name) && $this->define($name)->isMany();
    }

    function create($name)
    {

    }

    function offsetExists($offset)
    {
        return $this->has($offset);
    }

    function offsetGet($offset)
    {
        return $this->define($offset);
    }

    function offsetSet($offset, $value)
    {

    }

    function offsetUnset($offset)
    {

    }
}