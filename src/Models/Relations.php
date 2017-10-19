<?php

namespace Skvn\Crud\Models;

use ArrayAccess;
use Skvn\Crud\Exceptions\ConfigException;

class Relations implements ArrayAccess
{
    protected $model;
    protected $relations = [];
    protected $saving = false;

    public function __construct(CrudModel $model)
    {
        $this->model = $model;
    }

    public function has($name)
    {
        //return array_key_exists('relation', $this->model->getField($this->stripName($name)[0]));
        return array_key_exists('relation', $this->model->getField($name));
    }

//    protected function stripName($name)
//    {
//        if (strpos($name, '_') !== false) {
//            $split = explode('_', $name);
//            if (count($split) == 2 && in_array($split[1], ['ids', 'first', 'count'])) {
//                return $split;
//            }
//        }
//
//        return [$name, null];
//    }

    public function defined($name)
    {
        return isset($this->relations[$name]);
    }

    public function defineAll()
    {
        foreach ($this->model->confParam('fields') as $field => $conf) {
            if (! empty($conf['relation'])) {
                $this->define($field);
            }
        }
    }

    public function define($name)
    {
        if (! $this->defined($name)) {
            $info = $this->model->getField($name, true);
            $class = $this->model->getApp()['config']->get('crud_common.relations')[$info['relation']] ?? null;
            if (empty($class)) {
                throw new ConfigException('Unknown relation '.$info['relation'].' on model '.get_class($this->model));
            }
            $this->relations[$name] = new $class($this->model, $info);
            $this->relations[$name]->create();
        }

        return $this->relations[$name];
    }

    public function undef($name)
    {
        if ($this->defined($name)) {
            unset($this->relations[$name]);
        }
    }

    public function undefAll()
    {
        $this->model->setRelations([]);
        foreach ($this->relations as $r => $rel) {
            $this->undef($r);
        }
    }

    public function getRelation($name)
    {
        return $this->define($name)->getRelation();
    }

    public function getAll()
    {
        $this->defineAll();
        $data = [];
        foreach ($this->relations as $name => $rel) {
            $data[$name] = $rel->isDirty() ? $rel->getDirtyValue() : $rel->get();
        }

        return $data;
    }

    public function get($name)
    {
        return $this->define($name)->get();
    }

//    public function getAny($name)
//    {
//        $rel = $this->stripName($name);
//        switch ($rel[1]) {
//            case 'ids':
//                return $this->getIds($rel[0]);
//            case 'first':
//                return $this->get($rel[0])->first();
//            case 'count':
//                return $this->get($rel[0])->count();
//            default:
//                return $this->get($rel[0]);
//
//        }
//    }

    public function getErrors()
    {
        $errors = [];
        foreach ($this->relations as $relation) {
            $errors = array_merge($errors, $relation->getErrors());
        }

        return $errors;
    }

    public function set($name, $value)
    {
        $this->define($name)->set($value);

        return $this;
    }

    public function save($name = null)
    {
        if ($this->saving) {
            return;
        }
        $this->saving = true;
        foreach ($this->relations as $rel_name => $relation) {
            if (! empty($name) && $name != $rel_name) {
                continue;
            }
            if ($relation->isDirty()) {
                $relation->save();
                $relation->resetDirty();
            }
        }
        $this->saving = false;

        return true;
    }

    public function delete()
    {
        foreach ($this->model->confParam('fields') ?? [] as $name => $field) {
            if (! empty($field['relation'])) {
                $this->define($name)->delete();
            }
        }
    }

    public function resolveReference($ref)
    {
        if (strpos($ref, '::') !== false) {
            list($rel, $attr) = explode('::', $ref);

            return ['rel' => $rel, 'attr' => $attr];
        }

        return false;
    }

    public function getIds($name)
    {
        return $this->define($name)->getIds();
    }

    public function isMany($name)
    {
        return $this->has($name) && $this->define($name)->isMany();
    }

    public function create($name)
    {
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->define($offset);
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetUnset($offset)
    {
    }
}
