<?php

namespace Skvn\Crud\Models;

class RelationBelongsToMany extends Relation
{
    public function create()
    {
        $table = $this->config['pivot_table'] ?? null;
        $self = $this->config['pivot_self_key'] ?? null;
        $foreign = $this->config['pivot_foreign_key'] ?? null;
        $this->relation = $this->model->belongsToMany(CrudModel:: resolveClass($this->config['model']), $table, $self, $foreign, $this->config['name']);
        $this->sort();

        return $this;
    }

    public function isMany()
    {
        return true;
    }

    public function delete($id = null)
    {
    }

    public function save()
    {
        if (is_array($this->dirtyValue)) {
            $this->relation->sync($this->dirtyValue);
        } else {
            $this->relation->sync([]);
        }
    }

    public function getIds()
    {
        return $this->get()->lists($this->createRelatedModel()->getKeyName())->all();
    }
}
