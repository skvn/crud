<?php

namespace Skvn\Crud\Form;

use Illuminate\Support\Collection;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Traits\FormControlCommonTrait;
use Skvn\Crud\Contracts\FormControlFilterable;

class EntitySelect extends Field implements FormControl,FormControlFilterable
{
    use FormControlCommonTrait;

    public function pullFromModel()
    {
        $this->value = $this->model->crudRelations->has($this->getName()) ? $this->model->crudRelations[$this->getName()]->getIds() : $this->model->getAttribute($this->getField());

        return $this;
    }

    public function getOutputValue():string
    {
        $olist = $this->getOptions();
        foreach ($olist as $o) {
            if ($o['value'] == $this->value) {
                return $o['text'];
            }
        }

        return $this->value;
    }

    public function controlType():string
    {
        return 'ent_select';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.ent_select';
    }

    public function controlWidgetUrl():string
    {
        return 'js/widgets/ent_select.js';
    }

    public function getOptions()
    {
        if (is_null($this->value)) {
            return [];
        }


        $class = CrudModel :: resolveClass($this->config['model']);
        $obj = new $class();
        $coll = $obj->find($this->getValueAsArray());

        return $this->flatOptions($coll, $obj);
    }

    private function getValueAsArray()
    {
        if (is_null($this->value)) {
            return [];
        }

        if (is_array($this->value)) {
            return $this->value;
        }
        if ($this->value instanceof Collection) {
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
        if ($modelObj->confParam('tree')) {
            $isTree = true;
            $levelCol = $modelObj->getTreeConfig('depth_column');
        } else {
            $isTree = false;
        }
        $options = [];
        foreach ($collection as $o) {
            $pref = '';
            if ($isTree) {
                $pref = str_pad('', ($o->$levelCol + 1), '-').' ';
                if ($o->$levelCol > 1) {
                    $pref .= $o->internal_code.'. ';
                }
            }
            $options[] = ['value' => $o->id, 'text' => $pref.$o->getTitle(), 'selected' => $this->isSelected($o->id)];
        }

        return $options;
    }

    public function getFilterCondition()
    {
        if (empty($this->value)) {
            return;
        }
        if (is_array($this->value) && count($this->value) == 1 && $this->value[0] == '') {
            return;
        }
        $join = null;
        if ($this->model->crudRelations->isMany($this->getName())) {
            //if (!empty($this->config['relation']) && $this->model->isManyRelation($this->config['relation']))
            $join = $this->name;
            $col = snake_case(class_basename($this->config['model'])).'_id';
        } else {
            $col = $this->getFilterColumnName();
        }

        $action = '=';
        if (is_array($this->value)) {
            $action = 'IN';
        }

        return [
            'join' => $join,
            'cond' => [$col, $action, $this->value],
        ];
    }
    
}
