<?php namespace Skvn\Crud\Form;


class Image extends File {

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