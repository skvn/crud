<?php namespace Skvn\Crud\Form;


class DateRange extends Range {


    function getValueFrom()
    {
        if (!empty($this->value)) {

            $from =  explode('~',$this->value)[0];
            if (!empty($from))
            {
                return date($this->config['format'],$from);
            }
        }
    }

    function getValueTo()
    {

        if (!empty($this->value))
        {
            $spl = explode('~',$this->value);
            if (!empty($spl[1]))
            {

                return date($this->config['format'],$spl[1]);
            }
        }
    }


} 