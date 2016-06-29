<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Tree extends Field implements WizardableField, FormControl{


    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {
        if ($this->model->isManyRelation($this->config['relation']))
        {
            $this->value =  $this->model->getRelationIds($this->name)->toArray();
        }
        else
        {
            if ($this->config['relation'] == CrudModel::RELATION_HAS_ONE)
            {
                $relation = $this->name;
                $this->value = [$this->$relation->getKey()];
            }
            else
            {
                $this->value = [$this->model->getAttribute($this->field)];
            }
        }
    }

    function pullFromData(array $data)
    {
        if (!empty($data[$this->name]))
        {
            if (is_array($data[$this->name]))
            {
                $this->value = $data[$this->name];
            }
            else
            {
                $this->value = explode(",", $data[$this->name]);
            }
        }
        else
        {
            $this->value = [];
        }
    }

    function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }


    function controlType():string
    {
        return "tree";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.tree";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/tree_control.js";
    }


    /**
     * Returns true if the  control can be used only for relation editing only
     *
     * @return bool
     */
    public function wizardIsForRelationOnly():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used only for relation editing
     *
     * @return bool
     */
    public function wizardIsForRelation():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used  for "many" - type relation editing
     *
     * @return bool
     */
    public function wizardIsForManyRelation():bool
    {
        return true;
    }


    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.tree";
    }

    function wizardCaption()
    {
        return "Tree";
    }







    public function getOptions()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        $modelObj = new $class();

        if (!empty($this->config['find']) && !empty($this->config['model']))
        {
            $method =  $method = "selectOptions" . studly_case($this->config['find']);

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
        elseif (!empty($this->config['find']) && empty($this->config['model']))
        {
            $method =  $method = "selectOptions" . studly_case($this->config['find']);
            return $this->model->{$method}($this->getName());
        }

    }

} 