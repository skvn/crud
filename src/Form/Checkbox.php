<?php

namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;

class Checkbox extends Field implements FormControl, FormControlFilterable
{
    use FormControlCommonTrait;

    public function getFilterCondition()
    {
        if (! empty($this->value)) {
            return ['cond' => [$this->getFilterColumnName(), '=',  $this->value]];
        }
    }

    public function controlType():string
    {
        return 'checkbox';
    }

    public function controlTemplate():string
    {
        return 'crud::crud.fields.checkbox';
    }

    public function controlWidgetUrl():string
    {
        return 'js/widgets/checkbox.js';
    }
}
