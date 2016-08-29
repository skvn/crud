<?php

namespace Skvn\Crud\Form;

use Illuminate\Support\Collection;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Skvn\Crud\Traits\FormControlCommonTrait;

class Tree extends Field implements FormControl
{
    use FormControlCommonTrait;

    public function pullFromModel()
    {
        if ($this->model->crudRelations->isMany($this->getName())) {
            //if ($this->model->isManyRelation($this->config['relation']))
            $this->value = $this->model->crudRelations->getIds($this->name);
        } else {
            if ($this->config['relation'] == 'hasOne') {
                $relation = $this->name;
                $this->value = [$this->$relation->getKey()];
            } else {
                $this->value = [$this->model->getAttribute($this->field)];
            }
        }
    }

    public function pullFromData(array $data)
    {
        if (!empty($data[$this->name])) {
            if (is_array($data[$this->name])) {
                $this->value = $data[$this->name];
            } else {
                $this->value = explode(',', $data[$this->name]);
            }
        } else {
            $this->value = [];
        }
    }

    public function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }

    public function controlType():string
    {
        return 'tree';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.tree';
    }

    public function controlWidgetUrl():string
    {
        return 'js/widgets/tree_control.js';
    }

    public function getOptions()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        $modelObj = new $class();

        if (!empty($this->config['find']) && !empty($this->config['model'])) {
            $method = $method = 'selectOptions'.studly_case($this->config['find']);

            $val = $this->getValue();
            if (!is_array($val)) {
                if ($val instanceof Collection) {
                    $val = $val->toArray();
                } elseif (is_scalar($val)) {
                    $val = [$val];
                }
            }

            return $modelObj->$method($this->getName(), $val);
        }

        if (!empty($this->config['model'])) {
            return CrudModelCollectionBuilder :: createTree($modelObj)
                        ->fetch();
        } elseif (!empty($this->config['find']) && empty($this->config['model'])) {
            $method = $method = 'selectOptions'.studly_case($this->config['find']);

            return $this->model->{$method}($this->getName());
        }
    }
}
