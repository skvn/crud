<?php

namespace Skvn\Crud\Form;

use Illuminate\Support\Collection;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Traits\FormControlCommonTrait;

class LinkedModelsTable extends Field implements FormControl
{
    use FormControlCommonTrait;

    public function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->name);
    }

    public function pullFromData(array $data)
    {
        $this->value = new Collection();
        $class = CrudModel :: resolveClass($this->config['model']);
        foreach ($data[$this->name] as $id => $entry) {
            $valid = true;
            $obj = $id > 0 ? $class :: findOrFail($id) : new $class();
            foreach ($this->getControls($obj, false) as $c) {
                $c->pullFromData($entry);
                $c->pushToModel();
                if ($c->config['required'] && !$c->getValue()) {
                    $valid = false;
                }
            }
            if ($valid) {
                $this->value->push($obj);
            }
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

    public function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }

    public function getControls(CrudModel $model = null, $forView = true)
    {
        $controls = [];
        if (is_null($model)) {
            $model = CrudModel :: createInstance($this->config['model']);
        }
        foreach ($this->config['fields'] as $field) {
            $control = Form :: createControl($model, $model->getField($field));
            if ($forView) {
                $id = $model->exists ? $model->getKey() : -1;
                $control->setField($this->name.'['.$id.']['.$control->config['name'].']');
                $control->config['required'] = false;
            }
            $controls[] = $control;
        }

        return $controls;
    }

    public function controlType():string
    {
        return 'linked_models_table';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.linked_models_table';
    }

    public function controlWidgetUrl():string
    {
        return 'js/widgets/editable_table.js';
    }
}
