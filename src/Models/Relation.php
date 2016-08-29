<?php

namespace Skvn\Crud\Models;

abstract class Relation
{
    protected $model;
    protected $config;
    protected $relation;
    protected $dirtyValue;
    protected $isDirty = false;

    public function __construct(CrudModel $model, $config)
    {
        $this->model = $model;
        $this->config = $config;
    }

    public function getRelation()
    {
        return $this->relation;
    }

    public function get()
    {
        //$name = Str :: camel($name);
        $name = $this->config['name'];
        if (!$this->model->relationLoaded($name)) {
            $data = $this->getRelation($name)->getResults();
            $this->model->setRelation($name, $data);
        }

        return $this->model->getRelation($name);
    }

    public function set($value)
    {
        $this->dirtyValue = $value;
        $this->isDirty = true;
    }

    protected function sort()
    {
        if (!empty($this->config['sort'])) {
            foreach ($this->config['sort'] as $col => $dir) {
                $this->relation->orderBy($col, $dir);
            }
        }
    }

    public function isDirty()
    {
        return $this->isDirty;
    }

    public function resetDirty()
    {
        $this->dirtyValue = null;
        $this->isDirty = false;
    }

    public function createRelatedModel()
    {
        return CrudModel :: createInstance($this->config['model']);
    }

    abstract public function create();

    abstract public function isMany();

    abstract public function delete($id = null);

    abstract public function save();

    abstract public function getIds();
}
