<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\CommonFieldWizardTrait;

class Number extends Field implements WizardableField{

    use CommonFieldWizardTrait;
    
    
    const TYPE = "number";


    public static function fieldDbType() {
        return 'integer';
    }
    
    static function controlTemplate()
    {
        return "crud::crud/fields/number.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/number.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "Number input";
    }

    static function controlFiltrable()
    {
        return true;
    }



    protected $filtrable = true;

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