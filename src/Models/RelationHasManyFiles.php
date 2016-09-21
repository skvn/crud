<?php

namespace Skvn\Crud\Models;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class RelationHasManyFiles extends Relation
{
    public function create()
    {
        $this->relation = $this->model->HasMany(CrudModel :: resolveClass($this->config['model']), $this->config['field'] ?? null);
        $this->sort();

        return $this;
    }

    public function isMany()
    {
        return true;
    }

    public function delete($id = null)
    {
        $col = $this->relation->getForeignKey();
        $delete = $this->config['on_delete'] ?? false;
        $this->get()->each(function ($item, $key) use ($delete, $col, $id) {
            if (! is_null($id)) {
                if ($id != $item->getKey()) {
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
        $class = CrudModel :: resolveClass($this->config['model']);

        $titles = $this->model->getApp()['request']->get($this->config['name'].'_title');
        foreach ($this->dirtyValue as $idx => $file) {
            if ($file instanceof UploadedFile) {
                $obj = new $class();
                $fileInfo = $obj->attachStoreTmpFile($file);
                $fileInfo['title'] = $titles[$idx] ?? $fileInfo['originalName'];
                if (! empty($fileInfo['originalPath'])) {
                    $obj->setAttribute($this->relation->getForeignKey(), $this->model->getKey());
                    $obj->attachStoreFile($fileInfo, $this->model->getFilesConfig($fileInfo['originalName']));
                }
            }
        }
        $titles = $this->model->getApp()['request']->get($this->config['name'].'_title');
        if ($titles && ! empty($titles)) {
            foreach ($titles as $iid => $title) {
                if ($iid > 0) {
                    $obj = $class :: findOrFail($iid);
                    $obj->title = $title;
                    $obj->save();
                }
            }
        }
    }

    public function getIds()
    {
        return $this->get()->lists($this->createRelatedModel()->getKeyName())->all();
    }
}
