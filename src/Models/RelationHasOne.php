<?php

namespace Skvn\Crud\Models;

class RelationHasOne extends Relation
{
    public function create()
    {
        $this->relation = $this->model->hasOne(CrudModel :: resolveClass($this->config['model']), $this->config['field'] ?? null);

        return $this;
    }

    public function isMany()
    {
        return false;
    }

    public function delete($id = null)
    {
        if (($this->config['on_delete'] ?? null) === 'delete') {
            $this->get()->delete();
        } else {
            $this->get()->{$this->config['field']} = null;
            $this->get()->save();
        }
    }

    public function save()
    {
        $obj = CrudModel :: createInstance($this->config['model'], null, $this->dirtyValue);
        $obj->setAttribute($this->config['field'], $this->model->getKey());
        $obj->save();
    }

    public function getIds()
    {
        return $this->get()->getKey();
    }
}
