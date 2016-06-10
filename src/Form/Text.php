<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;


class Text extends Field implements WizardableField, FormControl{

    
    use WizardCommonFieldTrait;
    

    function controlType()
    {
        return "text";
    }



    function controlTemplate()
    {
        return "crud::crud/fields/text.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/text.twig";
    }


    function wizardCaption()
    {
        return "Text input";
    }

    function wizardFiltrable()
    {
        return true;
    }


    function getValue()
    {
        if (is_null($this->value))
        {
            if (!in_array($this->getName(), $this->model->getHidden()))
            {
                $this->value = $this->model->getAttribute($this->getField());
            }
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->field;
            $value = str_replace(['*', '?'], ['%', '_'], $this->value);
            if (strpos($value, "~") === 0)
            {
                return ['cond' => [$col, 'NOT LIKE',  substr($value, 1) ]];
            }
            else
            {
                return ['cond' => [$col, 'LIKE',  $value ]];
            }
        }


    }
} 