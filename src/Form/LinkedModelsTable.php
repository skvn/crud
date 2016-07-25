<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;
use Skvn\Crud\Models\CrudModel;
use Illuminate\Support\Collection;


class LinkedModelsTable extends Field implements FormControl
{
    use FormControlCommonTrait;


    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->name);
    }

    function pullFromData(array $data)
    {
        $this->value = new Collection();
        $class = CrudModel :: resolveClass($this->config['model']);
        foreach ($data[$this->name] as $id => $entry)
        {
            $obj = $id > 0 ? $class :: findOrFail($id) : new $class();
            foreach ($this->getControls($obj) as $c)
            {
                $c->pullFromData($entry);
                $c->pushToModel();
            }
            $this->value->push($obj);
            //$obj->saveDirect();
        }



//        if (!empty($data['mail_id']) && !empty($data['mail_date']) && count($data['mail_id']) == count($data['mail_date']))
//        {
//            foreach ($data['mail_date'] as $idx => $date)
//            {
//                if (!empty($date) && strtotime($date) !== false)
//                {
//                    $this->value[] = ['id' => $data['mail_id'][$idx], 'maildate' => strtotime($date)];
//                }
//            }
//        }
    }

    function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }

    function getControls(CrudModel $model = null)
    {
        $controls = [];
        $x = is_null($model);
        if (is_null($model))
        {
            $model = CrudModel :: createInstance($this->config['model']);;
        }
        foreach ($this->config['fields'] as $field)
        {
            $control = Form :: createControl($model, $model->getField($field));
            if ($x)
            {
                $control->setField($this->name . '[-1][' . $control->config['name'] . ']');
            }
            $controls[] = $control;
        }
        return $controls;
    }


    function controlType():string
    {
        return "linked_models_table";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.linked_models_table";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/editable_table.js";
    }



}

