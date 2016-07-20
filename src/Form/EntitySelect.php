<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class EntitySelect extends Field implements  FormControl
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