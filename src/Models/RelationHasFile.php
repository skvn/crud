<?php

namespace Skvn\Crud\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class RelationHasFile extends Relation
{
    public function create()
    {
        $this->relation = $this->model->belongsTo(CrudModel :: resolveClass($this->config['model']), $this->config['field'], null, $this->config['name']);

        return $this;
    }

    public function set($value)
    {
        if (! empty($value)) {
            return parent :: set($value);
        }
    }

    public function isMany()
    {
        return false;
    }

    public function delete($id = null)
    {
        if (($this->config['on_delete'] ?? false) === 'delete') {
            $obj = $this->get();
            if ($obj) {
                $obj->delete();
            }
        }
        if (! is_null($id)) {
            $this->model->setAttribute($this->relation->getForeignKeyName(), null);
            $this->model->save();
        }
    }

    public function save()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        if ($this->dirtyValue instanceof UploadedFile) {
            $obj = $class :: findOrNew($this->model->getAttribute($this->config['field']));
            $fileInfo = $obj->attachStoreTmpFile($this->dirtyValue);
            if (! empty($fileInfo['originalPath'])) {
                $obj->attachStoreFile($fileInfo, $this->model->getFilesConfig($fileInfo['originalName']));
                $this->model->setAttribute($this->relation->getForeignKeyName(), $obj->getKey());
                $this->model->save();
            }
        }
    }

    public function getIds()
    {
        return $this->model->getAttribute($this->config['field']);
    }
}
