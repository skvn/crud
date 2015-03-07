<?php

namespace LaravelCrud\Form;


class Range extends Field {


    function getValueFrom()
    {
        if (!empty($this->value)) {
            return explode('~',$this->value)[0];
        }
    }

    function getValueTo()
    {

        if (!empty($this->value))
        {
            $spl = explode('~',$this->value);
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
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->config['column'];
            if ($split[0] != '' && $split[1] != '')
            {
                return ['cond' => [$col, 'BETWEEN', $split]];

            } elseif ($split[0] != ''){
                return ['cond' => [$col, '>=', $split[0]]];
            }
            elseif ($split[1] != ''){
                return ['cond' => [$col, '=<', $split[1]]];
            }
        }


    }
} 