<?php namespace Skvn\Crud\Models;

class RelationBelongsTo extends Relation
{
    function create()
    {
        $this->relation = $this->model->belongsTo(CrudModel :: resolveClass($this->config['model']), $this->config['field'], null, $this->config['name']);
        return $this;
    }

    function isMany()
    {
        return false;
    }

    function delete($id = null)
    {
        if (($this->config['on_delete'] ?? false) === "delete")
        {
            $this->get()->delete();
        }
        if (!is_null($id))
        {
            $this->model->setAttribute($this->relation->getForeignField(), null);
            $this->model->save();
        }
    }

    function get()
    {
        $val = parent :: get();
        if (is_null($val))
        {
            return CrudModel :: createInstance($this->config['model']);
        }
        return $val;
    }

    function save()
    {

    }

    function getIds()
    {
        return $this->model->getAttribute($this->config['field']);
    }
}