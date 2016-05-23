<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;

class Tree extends Field {

    public  function  getValue()
    {

        if (is_null($this->value)) {
            if ($this->model->isManyRelation($this->config['relation'])) {
                $this->value =  $this->model->getRelationIds($this->getName());
            } else {
                if ($this->config['relation'] == CrudModel::RELATION_HAS_ONE) {
                    $relation = $this->getName();
                    $this->value = $this->$relation->id;

                } else {
                    $this->value = $this->model->getAttribute($this->getName());
                }
            }
        }

        return $this->value;

    }

    function  getValueForDb()
    {
        $val = $this->getValue();
        if (is_string($val))
        {
            return explode(',',$val);
        }
    }

    public function getOptions()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        $modelObj = new $class();

        if (!empty($this->config['find']))
        {
            $method = $this->config['find'];
            $val = $this->getValue();
            if (!is_array($val))
            {
                if ($val instanceof Collection)
                {
                    $val = $val->toArray();
                } elseif (is_scalar($val))
                {
                    $val = [$val];
                }
            }
            return $modelObj->$method($this->getName(),$val);
        }

        if (!empty($this->config['model']))
        {
            return CrudModelCollectionBuilder :: createTree($modelObj)
                        ->fetch();
        }
        elseif (!empty($this->config['method_options']))
        {
            return $this->model->{$this->config['method_options']}($this->getName());
        }

    }

} 