<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Select extends Field implements  FormControl, FormControlFilterable
{

    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->crudRelations->has($this->getName()) ? $this->model->crudRelations[$this->getName()]->getIds() : $this->model->getAttribute($this->getField());
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


} 