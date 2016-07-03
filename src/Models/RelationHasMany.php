<?php namespace Skvn\Crud\Models;

class RelationHasMany extends Relation
{

    function create()
    {
        $this->relation = $this->model->HasMany(CrudModel :: resolveClass($this->config['model']), $this->config['field'] ?? null);
        $this->sort();
        return $this;
    }

    function isMany()
    {
        return true;
    }

    function delete($id = null)
    {
        $col = $this->relation->getForeignKey();
        $delete = $this->config['on_delete'] ?? false;
        $this->get()->each(function ($item, $key) use ($delete, $col, $id) {

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
                $item->$col = null;
                $item->save();
            }
        });

    }

    function save()
    {
        if (is_array($this->dirtyValue))
        {
            $oldIds = $this->relation->lists('id')->toArray();
            foreach ($this->dirtyValue as $id)
            {
                $obj = CrudModel :: createInstance($this->config['model'], null, $id);
                $this->relation->save($obj);
            }
            $toUnlink = array_diff($oldIds, $this->dirtyValue);
        }
        else
        {
            $toUnlink = $this->relation->lists('id')->toArray();
        }

        if ($toUnlink && is_array($toUnlink))
        {
            foreach ($toUnlink as $id)
            {
                $col = $this->relation->getForeignKey();
//                if (!empty($field['ref_column']))
//                {
//                    $col = $field['ref_column'];
//                }
//                else
//                {
//                    $col = $this->classViewName . '_id';
//                }
                $obj = CrudModel :: createInstance($this->config['model'], null, $id);
                if (($this->config['on_delete'] ?? false) === "delete")
                {
                    $obj->delete();
                }
                else
                {
                    $obj->$col = null;
                    $obj->save();
                }
            }
        }

    }

    function getIds()
    {
        return $this->get()->lists($this->createRelatedModel()->getKeyName())->all();
    }

}