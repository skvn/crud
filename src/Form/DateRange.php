<?php namespace Skvn\Crud\Form;


class DateRange extends Range {


    function getValueFrom()
    {
        if ($this->getValue())
        {
            $from =  explode('~',$this->getValue())[0];
            if (!empty($from))
            {
                return date($this->config['format'],$from);
            }
        }
    }

    function getValueTo()
    {

        if ($this->getValue())
        {
            $spl = explode('~',$this->getValue());
            if (!empty($spl[1]))
            {

                return date($this->config['format'],$spl[1]);
            }
        }
    }

    function getValueForList()
    {
        return $this->getValueFrom() . "-" . $this->getValueTo();
    }

    function prepareValueForDb($value)
    {
        return strtotime($value . ' 14:23');
    }

//    private function isInt()
//    {
//        return (empty($this->config['db_type']) ||$this->config['db_type'] == 'int');
//    }


//    function  getValueForDb()
//    {
//        if ($this->isInt())
//        {
//            return strtotime($this->getValue() . ' 14:23');
//        }
//        else
//        {
//            return date('Y-m-d',strtotime($this->getValue()));
//        }
//    }

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
                $to = '';
                if (isset($data[$this->name . '_from']))
                {
                    $from = strtotime($data[$this->name . '_from']);
                }
                if (isset($data[$this->name . '_to']))
                {
                    $to = strtotime($data[$this->name . '_to']);
                }
                $this->setValue($from . '~' . $to);
            }
        }

    }


} 