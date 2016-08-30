<?php

namespace Skvn\Crud\Models;

class RelationBelongsTo extends Relation
{
    public function create()
    {
        $this->relation = $this->model->belongsTo(CrudModel :: resolveClass($this->config['model']), $this->config['field'], null, $this->config['name']);

        return $this;
    }

    public function isMany()
    {
        return false;
    }

    public function delete($id = null)
    {
        if (($this->config['on_delete'] ?? false) === 'delete') {
            $this->get()->delete();
        }
        if (! is_null($id)) {
            $this->model->setAttribute($this->relation->getForeignField(), null);
            $this->model->save();
        }
    }

    public function get()
    {
        $val = parent :: get();
        if (is_null($val)) {
            return CrudModel :: createInstance($this->config['model']);
        }

        return $val;
    }

    public function save()
    {
    }

    public function getIds()
    {
        return $this->model->getAttribute($this->config['field']);
    }
}
