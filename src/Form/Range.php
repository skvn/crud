<?php namespace Skvn\Crud\Form;


class Range extends Field
{
    const TYPE = "range";



    static function controlTemplate()
    {
        return "crud::crud/fields/range.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/range.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "---";
    }

    static function controlFiltrable()
    {
        return false;
    }


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
                $this->setValue($from . '~' . $to);
            }
        }

    }

    function syncValue()
    {
        $this->model->setAttribute($this->getFromFieldName(), $this->prepareValueForDb($this->getValueFrom()));
        $this->model->setAttribute($this->getToFieldName(), $this->prepareValueForDb($this->getValueTo()));
    }

} 