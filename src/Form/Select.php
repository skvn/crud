<?php

namespace LaravelCrud\Form;


class Select extends Field {



    public function getOptions($empty_option=null)
    {
        $options = array();
        $options[] = ['value'=>'', 'text'=>$empty_option];

        if (!empty($this->config['select_options']))
        {
            $opts = [];
            foreach ($this->config['select_options'] as $k=>$v)
            {
                $selected = 0;
                if ($this->value) {
                    if (is_array($this->value)) {
                        if (in_array($k, $this->value)) {
                            $selected = 1;
                        }

                    } else {

                        if ($this->value == $k) {
                            $selected = 1;
                        }
                    }
                }
                $opts[] = ['value'=>$k, 'text'=>$v,'selected'=>$selected];
            }

        } else if (!empty($this->config['model']))
        {
            $opts =  $this->getModelOptions();
        }
        elseif (!empty($this->config['method_options']))
        {
            $this->value = $this->form->crudObj->getAttribute($this->getName());
            $opts = [];
            foreach ($this->form->crudObj->{$this->config['method_options']}() as $k => $v)
            {
                $opts[] = ['value' => $k, 'text' => $v, 'selected' => is_array($this->value) && in_array($k, $this->value) || !is_array($this->value) && $this->value == $k];
            }
        }
        else
        {
            $opts = array();
        }

        return array_merge($options, $opts);

    }

    private function getModelOptions()
    {

        $options = [];
        $class = '\App\Model\\'.$this->config['model'];


        $modelObj = new $class();
        $isTree = false;
        if (!empty($this->config['find']))
        {
            $method = $this->config['find'];
            $collection = $modelObj->$method();
        } else {
            if ($modelObj->config->get('tree')) {
//            $isTree = true;
//            $levelCol = $modelObj->config->get('tree_level_column');
//            $pathCol = $modelObj->config->get('tree_path_column');
                $coll = $modelObj->getListCollection();
                $collection = \App::make('CrudHelper')->prepareCollectionForView($coll, null, 'tree_flattened');

            } else {
                $collection = $class::all();
            }
        }


        if (!$this->value)
        {
            if (!empty($this->config['relation']) && $this->form->crudObj->config->isManyRelation($this->config['relation'])) {

                $this->value = $this->form->crudObj->getRelationIds($this->getName());


            } else {
                $this->value = $this->form->crudObj->getAttribute($this->getName());
            }

        }

        if ($this->isGrouped())
        {
           $options = $this->groupedOptions($collection);

        } else
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
        foreach ($collection as $o) {
            $selected = 0;
            if ($this->value) {
                if (is_array($this->value)) {
                    if (in_array($o->id, $this->value)) {
                        $selected = 1;
                    }

                } else {

                    if ($this->value == $o->id) {
                        $selected = 1;
                    }
                }
            }

            if (count($dataCols))
            {
                foreach ($dataCols as $col)
                {
                    $data[$col] = $o->getDescribedColumnValue($col);
                }
            }
            $option = ['value' => $o->id, 'text' =>$o->internal_code.'. '.  $o->title, 'selected' => $selected,'data'=>$data];
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
        if ($modelObj->config->get('tree')) {
            $isTree = true;
            $levelCol = $modelObj->config->get('tree_level_column');
            //$pathCol = $modelObj->config->get('tree_path_column');
        } else
        {
            $isTree = false;
        }
        $options = [];
        foreach ($collection as $o) {
            $selected = 0;
            if ($this->value) {
                if (is_array($this->value)) {
                    if (in_array($o->id, $this->value)) {
                        $selected = 1;
                    }

                } else {

                    if ($this->value == $o->id) {
                        $selected = 1;
                    }
                }
            }

            $pref = '';

            if ($isTree) {

                $pref = str_pad('', ($o->$levelCol + 1), '-') . ' ';
            }
            $pref .= $o->internal_code.'. ';
            $options[] = ['value' => $o->id, 'text' => $pref . $o->title, 'selected' => $selected];
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
        if (!empty($this->config['relation']) && $this->form->crudObj->config->isManyRelation($this->config['relation']))
        {
            $join = $this->name;
            $col = snake_case(class_basename($this->config['model'])).'_id';

        } else {
            $col = $this->getFilterColumnName();
        }

        $action = "=";
        if (is_array($this->value))
        {
            $action = 'IN';
        }

        return ['join' => $join,
            'cond'=>[$col,$action, $this->value]];

    }

} 