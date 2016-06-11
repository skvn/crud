<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFiltrable;
use Skvn\Crud\Traits\FormControlCommonTrait;

class Checkbox extends Field implements WizardableField, FormControl, FormControlFiltrable
{
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            return ['cond' => [$this->getFilterColumnName(), '=',  $this->value ]];
        }
    }

    function controlType():string
    {
        return "checkbox";
    }

    function controlTemplate():string
    {
        return "crud::crud/fields/checkbox.twig";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/checkbox.js";
    }

    public function wizardDbType()
    {
        return 'boolean';
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/checkbox.twig";
    }

    function wizardCaption()
    {
        return "Checkbox";
    }


}