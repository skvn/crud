<?php namespace Skvn\Crud\Traits;

trait FormControlCommonTrait
{
    function controlWidgetUrl():string
    {
        return false;
    }

    function controlValidateConfig():bool
    {
        return true;
    }

    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->field);
    }

    function pullFromData(array $data)
    {
        $this->value = isset($data[$this->field]) ? $data[$this->field] : null;
    }


    function pushToModel()
    {
        $this->model->setAttribute($this->field, $this->value);
    }

    function getValue()
    {
        return $this->value;
    }

    function getOutputValue():string
    {
        return $this->value;
    }

    function setValue($val)
    {
        $this->value =  $val;
    }

}