<?php namespace Skvn\Crud\Models;

class RelationMorphTo extends Relation
{
    function create()
    {
        $this->relation = $this->model->morphTo($this->config['name'], $this->config['field_ref_class'], $this->config['field_ref_id']);
        return $this;
    }

    function isMany()
    {
        return false;
    }

    function delete($id = null)
    {
    }

    function save()
    {

    }

    function getIds()
    {
        return [];
        //return $this->model->getAttribute($this->config['field']);
    }
}