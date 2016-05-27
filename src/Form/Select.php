<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;

class Select extends Field {


    function getValue()
    {
        if (is_null($this->value))
        {
            if (!in_array($this->getName(), $this->model->getHidden()))
            {
                $this->value = $this->model->getAttribute($this->getName());
            }
        }

        return $this->value;
    }

    function getValueForList()
    {
        $v = $this->getValue();
        if (!empty($this->config['select_options']))
        {
            if (isset($this->config['select_options'][$v]))
            {
                return is_array($this->config['select_options'][$v]) ? $this->config['select_options'][$v]['caption'] : $this->config['select_options'][$v];
            }
        }
        return $v;
    }


    public function getOptions($empty_option=null)
    {
        //$options = array();
//        if (!empty($this->config['select_options']))
//        {
//            if (!$this->value)
//            {
//                if (!empty($this->config['relation'])
//                    &&
//                    $this->model->isManyRelation($this->config['relation']))
//                {
//                    $this->value = $this->model->getRelationIds($this->getName());
//                }
//                else if (!empty($this->config['relation'])
//                    && $this->config['relation'] == CrudModel::RELATION_HAS_ONE)
//                {
//                    $relation = $this->getName();
//                    $this->value = $this->$relation->id;
//                }
//                else
//                {
//                    $this->value = $this->model->getAttribute($this->getName());
//                }
//            }
//            $opts = [];
//            if (!is_array($this->config['select_options']))
//            {
//                $this->config['select_options'] = $this->model->getAttribute($this->config['select_options']);
//            }
//
//            foreach ($this->config['select_options'] as $k => $v)
//            {
//                $txt = is_array($v) ? $v['caption'] : $v;
//                $opts[] = ['value' => $k, 'text' => $txt];
//            }
//
//        }
        $opts = [];
        if (!empty($this->config['model']))
        {
            $opts =  $this->getModelOptions();
        }
        elseif (!empty($this->config['method_options']))
        {
            $this->value = $this->model->getAttribute($this->getName());
            $opts = [];
            $method = $this->config['method_options'];
            if (method_exists($this->model, $method))
            {
                die('111');
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
//                $collection = $class::all();
                $collection = CrudModelCollectionBuilder :: create($modelObj)->fetch();
//                $coll = $modelObj->getListCollection();
//                $collection = $modelObj->prepareCollectionForView($coll, null, 'tree_flattened');
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
                $this->value = $this->model->getAttribute($this->getName());
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