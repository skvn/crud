<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFiltrable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Text extends Field implements WizardableField, FormControl, FormControlFiltrable{

    
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {
        if (!in_array($this->name, $this->model->getHidden()))
        {
            $this->value = $this->model->getAttribute($this->field);
        }
    }

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            $col = $this->filterColumnName();
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


    function controlType():string
    {
        return "text";
    }

    function controlTemplate():string
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


}