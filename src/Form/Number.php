<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;


class Number extends Field implements WizardableField, FormControl{

    use WizardCommonFieldTrait;
    protected $filtrable = true;

    

    function controlType()
    {
        return "number";
    }

    public function wizardDbType() {
        return 'integer';
    }
    
    function controlTemplate()
    {
        return "crud::crud/fields/number.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/number.twig";
    }


    function wizardCaption()
    {
        return "Number input";
    }

    function wizardFiltrable()
    {
        return true;
    }




    function getValue()
    {
        if (!$this->value)
        {
            if (!in_array($this->getName(), $this->model->getHidden()))
            {
                $this->value = $this->model->getAttribute($this->getField());
            }
        }

        return $this->value;
    }

}