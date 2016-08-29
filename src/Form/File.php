<?php

namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;

class File extends Field implements FormControl
{
    use FormControlCommonTrait;

    public function pullFromModel()
    {
        if ($this->model->getAttribute($this->field)) {
            $this->value = $this->model->getAttribute($this->name);
        }
    }

    public function pullFromData(array $data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }

    public function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }

    public function controlType():string
    {
        return 'file';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.file';
    }
}
