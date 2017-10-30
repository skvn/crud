<?php

namespace Skvn\Crud\Models;

class RelationHasMany extends Relation
{
    public function create()
    {
        $this->relation = $this->model->hasMany(CrudModel :: resolveClass($this->config['model']), $this->config['field'] ?? null);
        $this->sort();

        return $this;
    }

    public function isMany()
    {
        return true;
    }

    public function delete($id = null)
    {
        $col = $this->relation->getForeignKeyName();
        $delete = $this->config['on_delete'] ?? false;
        $this->get()->each(function ($item, $key) use ($delete, $col, $id) {
            if (! is_null($id)) {
                if ($item->getKey() != $id) {
                    return;
                }
            }
            if ($delete === 'delete') {
                $item->delete();
            } else {
                $item->$col = null;
                $item->save();
            }
        });
    }

    public function save()
    {
        if (is_array($this->dirtyValue)) {
            $oldIds = $this->getIds();
            foreach ($this->dirtyValue as $id) {
                $obj = CrudModel :: createInstance($this->config['model'], null, $id);
                $this->relation->save($obj);
            }
            $toUnlink = array_diff($oldIds, $this->dirtyValue);
        } else {
            $toUnlink = $this->getIds();
        }

        if ($toUnlink && is_array($toUnlink)) {
            foreach ($toUnlink as $id) {
                $col = $this->relation->getForeignKeyName();
//                if (!empty($field['ref_column']))
//                {
//                    $col = $field['ref_column'];
//                }
//                else
//                {
//                    $col = $this->classViewName . '_id';
//                }
                $obj = CrudModel :: createInstance($this->config['model'], null, $id);
                if (($this->config['on_delete'] ?? false) === 'delete') {
                    $obj->delete();
                } else {
                    $obj->$col = null;
                    $obj->save();
                }
            }
        }
    }

    public function getErrors()
    {
        $errors = [];
        foreach ($this->get() as $obj) {
            if ($obj->hasErrors()) {
                foreach ($obj->getErrors() as $err) {
                    $errors[] = ['field' => $obj->classViewName.'::'.$err['field'], 'message' => $err['message']];
                }
            }
        }

        return $errors;
    }

    public function getIds()
    {
        return $this->get()->pluck($this->createRelatedModel()->getKeyName())->all();
    }
}
