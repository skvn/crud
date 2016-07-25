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
//        if (($this->config['on_delete'] ?? false) === "delete")
//        {
//            $this->get()->delete();
//        }
//        if (!is_null($id))
//        {
//            $this->model->setAttribute($this->relation->getForeignField(), null);
//            $this->model->saveDirect();
//        }
    }

//    function get()
//    {
//        $val = parent :: get();
//        if (is_null($val))
//        {
//            return CrudModel :: createInstance($this->config['model']);
//        }
//        return $val;
//    }

    function save()
    {

    }

    function getIds()
    {
        return [];
        //return $this->model->getAttribute($this->config['field']);
    }
}