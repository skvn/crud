<?php namespace Skvn\Crud\Form;


class Range extends Field
{

    function getValue()
    {
        if (is_null($this->value))
        {
            if (!empty($this->config['fields']))
            {
                $this->value = $this->model->getAttribute($this->config['fields'][0]) . "~" . $this->model->getAttribute($this->config['fields'][1]);
            }
        }
        return $this->value;
    }


    function getValueFrom()
    {
        if ($this->getValue())
        {
            return explode('~',$this->getValue())[0];
        }
    }

    function getValueTo()
    {
        if ($this->getValue())
        {
            $spl = explode('~',$this->getValue());
            if (isset($spl[1]))
            {
                return $spl[1];
            }
        }
    }

    function getDefaultFrom()
    {
        if (!empty($this->config['default']))
        {
            return explode('~',$this->config['default'])[0];
        }
    }

    function getDefaultTo()
    {

        if (!empty($this->config['default']))
        {
            $spl = explode('~',$this->config['default']);
            if (isset($spl[1]))
            {

                return $spl[1];
            }
        }
    }

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            $split = explode('~',$this->value);
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->field;
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

    function importValue($data)
    {
        if (!empty($data[$this->name]) && strpos($data[$this->name],'~') !== false)
        {
            $this->setValue($data[$this->name]);

        }
        else
        {
            if (isset($data[$this->name . '_from']) || isset ($data[$this->name . '_to']))
            {
                $from = 0;
                $to = 0;
                if (isset($data[$this->name . '_from']))
                {
                    $from = $data[$this->name . '_from'];
                }
                if (isset($data[$this->name . '_to']))
                {
                    $to = $data[$this->name . '_to'];
                }
                $this->setValue($from . '~' . $to);
            }
        }

    }

} 