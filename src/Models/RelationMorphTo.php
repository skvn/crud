<?php

namespace Skvn\Crud\Models;

class RelationMorphTo extends Relation
{
    public function create()
    {
        $this->relation = $this->model->morphTo($this->config['name'], $this->config['field_ref_class'], $this->config['field_ref_id']);

        return $this;
    }

    public function isMany()
    {
        return false;
    }

    public function delete($id = null)
    {
    }

    public function save()
    {
    }

    public function getIds()
    {
        return [];
        //return $this->model->getAttribute($this->config['field']);
    }
}
