<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Select extends Field implements WizardableField, FormControl, FormControlFilterable
{
    
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->crudRelations->has($this->getName()) ? $this->model->crudRelations[$this->getName()]->getIds() : $this->model->getAttribute($this->getField());
//        if (!in_array($this->name, $this->model->getHidden()))
//        {
//            $this->value = $this->model->getAttribute($this->field);
//        }


//        if ($this->model->crudRelations->isMany($this->getName()))
//        //if (!empty($this->config['relation']) && $this->model->isManyRelation($this->config['relation']))
//        {
//            $this->value = $this->model->crudRelations->getIds($this->getName());
//        }
//        else if (!empty($this->config['relation'])
//            && $this->config['relation'] == "hasOne")
//        {
//            $relation = $this->getName();
//            $this->value = $this->model->$relation->id;
//        }
//        else
//        {
//            $this->value = $this->model->getAttribute($this->getField());
//        }

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
        return "select";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.select";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/select.js";
    }


    /**
     * Returns true if the  control can be used only for relation editing only
     *
     * @return bool
     */
    public function wizardIsForRelationOnly():bool
    {
        return false;
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
        return "Select";
    }



    public function getOptions()
    {
        $opts = [];

        if (!empty($this->config['find']) && empty($this->config['model']))
        {

            $method = "selectOptions" . studly_case($this->config['find']);
            if (method_exists($this->model, $method))
            {
                $opts = $this->model->$method();
            }

        }
        elseif (!empty($this->config['model']))
        {
            $opts =  $this->getModelOptions();
        }
        else
        {
            $opts = array();
        }
        if ($this->isGrouped())
        {
            foreach ($opts as $gid => $g)
            {
                foreach ($g['options'] as $iid => $opt)
                {
                    $opts[$gid]['options'][$iid]['selected'] = $this->isSelected($opt['value']);
                }
            }
        }
        else
        {
            foreach ($opts as $idx => $opt)
            {
                $opts[$idx]['selected'] = $this->isSelected($opt['value']);
            }
        }

        //return array_merge($options, $opts);
        return $opts;

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

//    private function getSelectedOptions()
//    {
//        if (is_null($this->value))
//        {
//            return [];
//        }
//
//
//        $class = CrudModel :: resolveClass($this->config['model']);
//        $obj = new $class();
//        $coll = $obj->find($this->getValueAsArray());
//        return $this->flatOptions($coll, $obj);
//    }

    function getDataAttrs($option)
    {
        $data = [];
        foreach ($option as $k => $v)
        {
            if (!in_array($k, ['value', 'text']))
            {
                $data[] = 'data-' . $k . '="'.$v.'"';
            }
        }
        return implode(" ", $data);
    }

    private function getModelOptions()
    {
        $class = CrudModel :: resolveClass($this->config['model']);

        $modelObj = CrudModel :: createInstance($this->config['model'], null, is_numeric($this->value) ? $this->value : null);
        //$modelObj = new $class();
        if (!empty($this->config['find']))
        {
            $method = $method = "selectOptions" . studly_case($this->config['find']);
            return $modelObj->$method($this->model);
        }
        else
        {
            if ($modelObj->confParam('tree'))
            {
                $collection = CrudModelCollectionBuilder :: create($modelObj)->fetch();
            }
            else
            {
                $query = $class :: query();
                if (!empty($this->config['sort']))
                {
                    foreach ($this->config['sort'] as $c => $d)
                    {
                        $query->orderBy($c, $d);
                    }
                }
                $collection = $query->get();
            }
        }


        if ($this->isGrouped())
        {
           $options = $this->groupedOptions($collection);
        }
        else
        {
            $options = $this->flatOptions($collection, $modelObj);
        }

        return $options;
    }

    public function isGrouped()
    {
        if (!empty($this->config['options']['group_by'])) {
            return true;
        }
        return false;
    }

    private function groupedOptions($collection)
    {
        $options = [];
        $groupBy = $this->config['options']['group_by'];
        $dataCols = (!empty($this->config['options']['data'])?$this->config['options']['data']:[]);
        $data = [];
        foreach ($collection as $o)
        {
            if (count($dataCols))
            {
                foreach ($dataCols as $col)
                {
                    $data[$col] = $o->formatted($col);
                }
            }
            $option = ['value' => $o->id, 'text' =>$o->internal_code.'. '.  $o->title, 'selected' => $this->isSelected($o->id),'data'=>$data];
            $grVal = $o->formatted($groupBy);
            if (empty($options[$grVal]))
            {
                $options[$grVal] = ['title'=>$grVal,'options'=>[]];
            }
            $options[$grVal]['options'][] = $option;
        }
        return $options;
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
            $options[] = ['value' => $o->getKey(), 'text' => $pref . $o->getTitle(), 'selected' => $this->isSelected($o->getKey())];
        }
        return $options;
    }


    function getFilterCondition()
    {
        if (empty($this->value))
        {
            return;
        }
        if (is_array($this->value) && count($this->value) == 1 && $this->value[0] == '')
        {
            return;
        }
        $join = null;
        if ($this->model->crudRelations->isMany($this->getName()))
        //if (!empty($this->config['relation']) && $this->model->isManyRelation($this->config['relation']))
        {
            $join = $this->name;
            $col = snake_case(class_basename($this->config['model'])).'_id';

        }
        else
        {
            $col = $this->getFilterColumnName();
        }

        $action = "=";
        if (is_array($this->value))
        {
            $action = 'IN';
        }

        return [
            'join' => $join,
            'cond'=>[$col,$action, $this->value]
        ];
    }//

    public function wizardCallbackFieldConfig (&$fieldKey,array &$fieldConfig,  $modelPrototype)
    {
        if (!empty($fieldConfig['property_name']))
        {
            $fieldKey = $fieldConfig['property_name'];
            unset($fieldConfig['property_name']);
        }

        if (!empty($fieldConfig['relation'])) {
            if (in_array($fieldConfig['relation'], [
                "belongsToMany",
                "hasMany"
            ])) {
                $fieldConfig['multiple'] = true;
            }
        }
    }

} 