<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;

class Tree extends Field {

    public  function  getValue()
    {
        if ($this->model->isManyRelation($this->config['relation'])) {
            return $this->model->getRelationIds($this->getName());
        }
        if ($this->config['relation'] == CrudModel::RELATION_HAS_ONE)
        {
            $relation = $this->getName();
            return $this->$relation->id;

        } else {
            return $this->model->getAttribute($this->getName());
        }

    }

    public function getOptions()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        $modelObj = new $class();

        if (!empty($this->config['find']))
        {
            $method = $this->config['find'];
            return $modelObj->$method($this->getValue());
        }

        if (!empty($this->config['model']))
        {
            return CrudModelCollectionBuilder :: createTree($modelObj)
                        ->fetch();
        }
        elseif (!empty($this->config['method_options']))
        {
            return $this->model->{$this->config['method_options']}();
        }

    }

} 