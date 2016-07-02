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

    function delete()
    {

    }

    function save()
    {

    }
}