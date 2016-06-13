<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Range extends Field implements WizardableField, FormControl, FormControlFilterable
{
    
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {
        if (!empty($this->config['fields']))
        {
            $this->value = $this->model->getAttribute($this->config['fields'][0]) . "~" . $this->model->getAttribute($this->config['fields'][1]);
        }
    }

    function pushToModel()
    {
        $this->model->setAttribute($this->getFromFieldName(), $this->getValueFrom());
        $this->model->setAttribute($this->getToFieldName(), $this->getValueTo());
    }

    function getFromFieldName()
    {
        if (!empty($this->config['fields']))
        {
            return $this->config['fields'][0];
        }
        return $this->name . "_from";
    }

    function getToFieldName()
    {
        if (!empty($this->config['fields']))
        {
            return $this->config['fields'][1];
        }
        return $this->name . "_to";
    }

    function getValueFrom()
    {
        if (strpos($this->value ?? "", "~") !== false)
        {
            return explode('~',$this->value)[0];
        }
    }

    function getValueTo()
    {
        if (strpos($this->value ?? "", "~") !== false)
        {
            return explode("~", $this->value)[1];
        }
    }

    function getDefaultFrom()
    {
        if (!empty($this->config['default']) && strpos($this->config['default'], "~") !== false)
        {
            return explode('~',$this->config['default'])[0];
        }
    }

    function getDefaultTo()
    {
        if (!empty($this->config['default']) && strpos($this->config['default'], "~") !== false)
        {
            return explode('~',$this->config['default'])[1];
        }
    }

    function pullFromData(array $data)
    {
        if (!empty($data[$this->name]) && strpos($data[$this->name],'~') !== false)
        {
            $this->value = $data[$this->name];
        }
        else
        {
            if (isset($data[$this->getFromFieldName()]) || isset ($data[$this->getToFieldName()]))
            {
                $from = 0;
                $to = 0;
                if (isset($data[$this->getFromFieldName()]))
                {
                    $from = $data[$this->getFromFieldName()];
                }
                if (isset($data[$this->getToFieldName()]))
                {
                    $to = $data[$this->getToFieldName()];
                }
                $this->value = $from . '~' . $to;
            }
        }

    }

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            $split = explode('~',$this->value);
            $col = $this->getFilterColumnName();
            if ($split[0] != '' && $split[1] != '')
            {
                return ['cond' => [$col, 'BETWEEN', $split]];
            }
            elseif ($split[0] != '')
            {
                return ['cond' => [$col, '>=', $split[0]]];
            }
            elseif ($split[1] != '')
            {
                return ['cond' => [$col, '=<', $split[1]]];
            }
        }
    }




    function controlType():string
    {
        return "range";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.range";
    }

    public function wizardDbType()
    {
        return '';
    }

    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.range";
    }


    function wizardCaption()
    {
        return "Range";
    }







} 