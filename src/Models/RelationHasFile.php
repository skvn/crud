<?php namespace Skvn\Crud\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;


class RelationHasFile extends Relation
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

    function delete($id = null)
    {
        if (($this->config['on_delete'] ?? false) === "delete")
        {
            $obj = $this->get();
            if ($obj)
            {
                $obj->delete();
            }
        }
        if (!is_null($id))
        {
            $this->model->setAttribute($this->relation->getForeignKey(), null);
            $this->model->save();
        }
    }

    function save()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        if ($this->dirtyValue instanceof UploadedFile)
        {
            $obj = $class :: findOrNew($this->model->getAttribute($this->config['field']));
            $fileInfo = $obj->attachStoreTmpFile($this->dirtyValue);
            if (!empty($fileInfo['originalPath']))
            {
                $obj->attachStoreFile($fileInfo, $this->model->getFilesConfig($fileInfo['originalName']));
                $this->model->setAttribute($this->relation->getForeignKey(), $obj->getKey());
                $this->model->save();
            }
        }
    }

    function getIds()
    {
        return $this->model->getAttribute($this->config['field']);
    }

}