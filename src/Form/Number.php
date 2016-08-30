<?php

namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;

class Number extends Field implements FormControl, FormControlFilterable
{
    use FormControlCommonTrait;

    public function pullFromModel()
    {
        if (! in_array($this->name, $this->model->getHidden())) {
            $this->value = $this->model->getAttribute($this->field);
        }
    }

    public function getFilterCondition()
    {
        if (! empty($this->value)) {
            return ['cond' => [$this->getFilterColumnName(), '=',  $this->value]];
        }
    }

    public function controlType():string
    {
        return 'number';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.number';
    }
}
