<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Traits\CommonFieldWizardTrait;

class Tree extends Field implements WizardableField{


    use CommonFieldWizardTrait;
    
    const TYPE = "tree";

    /**
     * Returns true if the  control can be used only for relation editing only
     *
     * @return bool
     */
    public static function controlIsForRelationOnly():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used only for relation editing
     *
     * @return bool
     */
    public static function controlIsForRelation():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used  for "many" - type relation editing
     *
     * @return bool
     */
    public static function controlIsForManyRelation():bool
    {
        return true;
    }

    static function controlTemplate()
    {
        return "crud::crud/fields/tree.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/tree.twig";
    }

    static function controlWidgetUrl()
    {
        return "js/widgets/tree_control.js";
    }

    static function controlCaption()
    {
        return "---";
    }

    static function controlFiltrable()
    {
        return false;
    }


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
                    $this->value = $this->model->getAttribute($this->getField());
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