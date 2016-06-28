<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;


class EntitySelect extends Field implements WizardableField, FormControl
{
    
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {


        if (!empty($this->config['relation']) && $this->model->isManyRelation($this->config['relation']))
        {
            $this->value = $this->model->getRelationIds($this->getName());
        }
        else if (!empty($this->config['relation'])
            && $this->config['relation'] == CrudModel::RELATION_HAS_ONE)
        {
            $relation = $this->getName();
            $this->value = $this->model->$relation->id;
        }
        else
        {
            $this->value = $this->model->getAttribute($this->getField());
        }

        return $this;
    }

    function getOutputValue():string
    {
        $olist = $this->getOptions();
        foreach ($olist as $o)
        {
            if ($o['value'] == $this->value)
            {
                return $o['text'];
            }
        }
        return $this->value;
    }


    function controlType():string
    {
        return "ent_select";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.ent_select";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/ent_select.js";
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

    public function wizardDbType()
    {
        return 'integer';
    }


    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.select";
    }


    function wizardCaption()
    {
        return "Entity Select";
    }



    public function getOptions()
    {

        if (is_null($this->value))
        {
            return [];
        }


        $class = CrudModel :: resolveClass($this->config['model']);
        $obj = new $class();
        $coll = $obj->find($this->getValueAsArray());
        return $this->flatOptions($coll, $obj);
    }

    private function getValueAsArray()
    {
        if (is_null($this->value))
        {
            return [];
        }

        if (is_array($this->value))
        {
            return $this->value;
        }
        if ($this->value instanceof Collection)
        {
            return $this->value->toArray();
        }

        return [$this->value];
    }

    private function isSelected($idx)
    {
        $value = $this->getValueAsArray();
        return in_array($idx, $value);

    }



    private function flatOptions($collection, $modelObj)
    {
        if ($modelObj->confParam('tree'))
        {
            $isTree = true;
            $levelCol = $modelObj->getTreeConfig('depth_column');
        }
        else
        {
            $isTree = false;
        }
        $options = [];
        foreach ($collection as $o)
        {
            $pref = '';
            if ($isTree)
            {
                $pref = str_pad('', ($o->$levelCol + 1), '-') . ' ';
                if ($o->$levelCol>1)
                {
                    $pref .= $o->internal_code . '. ';
                }
            }
            $options[] = ['value' => $o->id, 'text' => $pref . $o->getTitle(), 'selected' => $this->isSelected($o->id)];
        }
        return $options;
    }


} 