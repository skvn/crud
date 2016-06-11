<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFiltrable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Select extends Field implements WizardableField, FormControl, FormControlFiltrable
{
    
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {
        if (!in_array($this->name, $this->model->getHidden()))
        {
            $this->value = $this->model->getAttribute($this->field);
        }
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
        return "crud::crud/fields/select.twig";
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
        return "crud::wizard/blocks/fields/select.twig";
    }


    function wizardCaption()
    {
        return "Select";
    }



    public function getOptions()
    {
        $opts = [];
        if (!empty($this->config['method_options']))
        {
            //$this->value = $this->model->getAttribute($this->getField());
            $opts = [];
            $method = $this->config['method_options'];
            if (method_exists($this->model, $method))
            {
                foreach ($this->model->$method() as $k => $v)
                {
                    $opts[] = ['value' => $k, 'text' => $v];
                }
            }
            else
            {
                $method = "selectOptions" . studly_case($this->config['method_options']);
                if (method_exists($this->model, $method))
                {
                    $opts = $this->model->$method();
                }
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
        foreach ($opts as $idx => $opt)
        {
            $opts[$idx]['selected'] = $this->isSelected($opt['value']);
        }

        
        //return array_merge($options, $opts);
        return $opts;

    }

    private function isSelected($idx)
    {
        if (is_null($this->value))
        {
            return false;
        }
        if (is_array($this->value))
        {
            return in_array($idx, $this->value);
        }

        if (is_object($this->value) && ($this->value instanceof Collection))
        {
            $val = $this->value->toArray();
            return in_array($idx, $val);
        }

        return $idx == $this->value;
    }

    private function getModelOptions()
    {
        $class = CrudModel :: resolveClass($this->config['model']);

        $modelObj = new $class();
        if (!empty($this->config['find']))
        {
            $method = $this->config['find'];
            $collection = $modelObj->$method();
        }
        else
        {
            if ($modelObj->confParam('tree'))
            {
                $collection = CrudModelCollectionBuilder :: create($modelObj)->fetch();
            }
            else
            {
                $collection = $class::all();
            }
        }

        if (!$this->value)
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
                    $data[$col] = $o->getDescribedColumnValue($col);
                }
            }
            $option = ['value' => $o->id, 'text' =>$o->internal_code.'. '.  $o->title, 'selected' => $this->isSelected($o->id),'data'=>$data];
            $grVal = $o->getDescribedColumnValue($groupBy);
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
            $options[] = ['value' => $o->id, 'text' => $pref . $o->getTitle(), 'selected' => $this->isSelected($o->id)];
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
        if (!empty($this->config['relation']) && $this->model->isManyRelation($this->config['relation']))
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
    }

} 