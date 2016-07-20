<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;
use Skvn\Crud\Models\CrudModel;
use Carbon\Carbon;



class Date extends Field implements  FormControl{


    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->field);
        if ($this->value && $this->value->timestamp < 10)
        {
            $this->value = null;
        }
//        if (!$this->value)
//        {
//            if ($this->isInt())
//            {
//                $this->value = time();
//            }
//            else
//            {
//                $this->value = (new \DateTime('now'));
//            }
//        }
    }

    function getOutputValue():string
    {
        if (empty($this->value))
        {
            return null;
        }
        return $this->value->format($this->config['format']);
//        if ($this->value instanceof Carbon)
//        {
//            return date($this->config['format'], $this->value->timestamp);
//        }
//        if ($this->isInt())
//        {
//            return date($this->config['format'], $this->value);
//        }
//
//        return $this->value;
    }

    function pullFromData(array $data)
    {
        if (!empty($data[$this->field]))
        {
            $this->value = Carbon :: parse($data[$this->field]);
//            if ($this->isInt())
//            {
//                $this->value = is_numeric($data[$this->field]) ? $data[$this->field] : strtotime($data[$this->field]);
//            }
//            else
//            {
//                $this->value = $data[$this->field];
//            }
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
        return "date";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.date";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/datetime.js";
    }

    function controlValidateConfig():bool
    {
        return !empty($this->config['format']);
    }



    private function isInt()
    {
        return (empty($this->config['db_type']) ||$this->config['db_type'] == 'int');
    }




} 