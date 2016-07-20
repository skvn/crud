<?php namespace Skvn\Crud\Form;



use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;
use Skvn\Crud\Models\CrudModel;
use Carbon\Carbon;


class DateTime extends Field implements  FormControl
{

    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->field);
        if ($this->value && $this->value->timestamp  < 10)
        {
            $this->value = null;
        }
    }

    function getOutputValue():string
    {
        if (empty($this->value))
        {
            return "";
        }
        return $this->value->format($this->config['format']);
    }

    function pullFromData(array $data)
    {
        if (!empty($data[$this->field]))
        {
            $this->value = Carbon :: parse($data[$this->field]);
        }
        else
        {
            $this->value = null;
        }
    }

    function configureModel(CrudModel $model, array $config)
    {
        $model->setDates($config['field']);
        return $config;
    }

    function controlType():string
    {
        return "date_time";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.date_time";
    }

    function controlValidateConfig():bool
    {
        return !empty($this->config['format']);
    }



    private function isInt()
    {
        return (empty($this->config['db_type']) || $this->config['db_type'] == 'int');
    }

} 