<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\CommonFieldWizardTrait;

class Image extends File implements WizardableField{


    use CommonFieldWizardTrait;
    
    const TYPE = "image";

    static function controlTemplate()
    {
        return "crud::crud/fields/image.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/image.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "Image";
    }

    static function controlFiltrable()
    {
        return false;
    }



}