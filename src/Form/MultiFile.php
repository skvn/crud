<?php

namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;

class MultiFile extends Field implements FormControl
{
    use FormControlCommonTrait;

    public function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->name);

        return $this;
    }

    public function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }

    public function pullFromData(array $data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }

    public function controlType():string
    {
        return 'multi_file';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.multi_file';
    }

    public function getExisting()
    {
        return $this->model->getAttribute($this->getName());
    }
}
