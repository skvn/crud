<?php namespace Skvn\Crud\Models;

class RelationMorphMany extends Relation
{

    function create()
    {
        $this->relation = $this->model->morphMany(CrudModel :: resolveClass($this->config['model']), $this->config['name'], $this->config['field_ref_class'], $this->config['field_ref_id']);
        $this->sort();
        return $this;
    }

    function isMany()
    {
        return true;
    }

    function delete($id = null)
    {
        $col_id = $this->relation->getForeignKey();
        $col_class = $this->relation->getPlainMorphType();
        $delete = $this->config['on_delete'] ?? false;
        $this->get()->each(function ($item, $key) use ($delete, $col_id, $col_class, $id) {

            if (!is_null($id))
            {
                if ($item->getKey() != $id)
                {
                    return;
                }
            }
            if ($delete  === "delete")
            {
                $item->delete();
            }
            else
            {
                $item->$col_id = null;
                $item->$col_class = null;
                $item->save();
            }
        });

    }

    function save()
    {
        if ($this->dirtyValue)
        {
            $oldIds = $this->relation->lists('id')->toArray();
            $ids = [];
            foreach ($this->dirtyValue as $obj)
            {
                if ($obj->exists)
                {
                    $ids[] = $obj->getKey();
                }
                $this->relation->save($obj);
            }
            $toUnlink = array_diff($oldIds, $ids);
        }
        else
        {
            $toUnlink = $this->relation->lists('id')->toArray();
        }

        if ($toUnlink && is_array($toUnlink))
        {
            foreach ($toUnlink as $id)
            {
                $col_id = $this->relation->getForeignKey();
                $col_class = $this->relation->getPlainMorphType();
                $obj = CrudModel :: createInstance($this->config['model'], null, $id);
                if (($this->config['on_delete'] ?? false) === "delete")
                {
                    $obj->delete();
                }
                else
                {
                    $obj->$col_id = null;
                    $obj->$col_class = null;
                    $obj->saveDirect();
                }
            }
        }

    }

    function getIds()
    {
        return $this->get()->lists($this->createRelatedModel()->getKeyName())->all();
    }

}