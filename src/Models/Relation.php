<?php namespace Skvn\Crud\Models;

abstract class Relation
{
    protected $model;
    protected $config;
    protected $relation;
    protected $dirtyValue;
    protected $isDirty = false;

    function __construct(CrudModel $model, $config)
    {
        $this->model = $model;
        $this->config = $config;
    }

    function getRelation()
    {
        return $this->relation;
    }

    function get()
    {
        //$name = Str :: camel($name);
        $name = $this->config['name'];
        if (!$this->model->relationLoaded($name))
        {
            $data = $this->getRelation($name)->getResults();
            $this->model->setRelation($name, $data);
        }
        return $this->model->getRelation($name);

    }

    function set($value)
    {
        $this->dirtyValue = $value;
        $this->isDirty = true;
    }

    protected function sort()
    {
        if (!empty($this->config['sort']))
        {
            foreach ($this->config['sort'] as $col => $dir)
            {
                $this->relation->orderBy($col, $dir);
            }
        }
    }

    function isDirty()
    {
        return $this->isDirty;
    }

    function resetDirty()
    {
        $this->dirtyValue = null;
        $this->isDirty = false;
    }

    function createRelatedModel()
    {
        return CrudModel :: createInstance($this->config['model']);
    }


    abstract function create();
    abstract function isMany();
    abstract function delete($id = null);
    abstract function save();
    abstract function getIds();
}