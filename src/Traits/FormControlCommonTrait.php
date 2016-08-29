<?php

namespace Skvn\Crud\Traits;

trait FormControlCommonTrait
{
    public function controlWidgetUrl():string
    {
        return false;
    }

    public function controlValidateConfig():bool
    {
        return true;
    }

    public function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->field);

        return $this;
    }

    public function pullFromData(array $data)
    {
        $this->value = isset($data[$this->field]) ? $data[$this->field] : null;

        return $this;
    }

    public function pushToModel()
    {
        $this->model->setAttribute($this->field, $this->value);

        return $this;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getOutputValue():string
    {
        return $this->value;
    }

    public function setValue($val)
    {
        $this->value = $val;

        return $this;
    }
}
