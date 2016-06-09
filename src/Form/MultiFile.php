<?php namespace Skvn\Crud\Form;


class MultiFile extends Field {

    const TYPE = "multi_file";


    static function controlTemplate()
    {
        return "crud::crud/fields/multi_file.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/multi_file.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "Multiple files";
    }

    static function controlFiltrable()
    {
        return false;
    }


    function getExisting()
    {
        return $this->model->getAttach($this->getName());
    }


} 