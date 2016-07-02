<?php namespace Skvn\Crud\Models;

class RelationHasOne extends Relation
{

    function create()
    {
        $this->relation = $this->model->hasOne(CrudModel :: resolveClass($this->config['model']), $this->config['field'] ?? null);
        return $this;
    }

    function isMany()
    {
        return false;
    }

    function delete()
    {
        if (($this->config['on_delete'] ?? null) === "delete")
        {
            $this->get()->delete();
        }
        else
        {
            $this->get()->{$this->config['field']} = null;
            $this->get()->save();
        }
    }

    function save()
    {
        $obj = CrudModel :: createInstance($this->config['model'], null, $this->dirtyValue);
        $obj->setAttribute($this->config['field'], $this->model->getKey());
        $obj->save();
    }
}