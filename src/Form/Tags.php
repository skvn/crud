<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Models\CrudModel;

class Tags extends Field {

    const TYPE = "tags";


    static function controlTemplate()
    {
        return "crud::crud/fields/tags.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/tags.twig";
    }

    static function controlWidgetUrl()
    {
        return "js/widgets/tags.js";
    }

    static function controlCaption()
    {
        return "---";
    }

    static function controlFiltrable()
    {
        return false;
    }


    function getValue()
    {
        if (!$this->value)
        {
            $class = CrudModel :: resolveClass($this->config['model']);
            $dummyModel = new $class();
            $ids = $this->model->getRelationIds($this->getName());
            if (count($ids)) {
                $collection = $class::findMany($ids);
                $this->value = $collection->pluck($dummyModel->confParam('title_field'));
            }
        }

        return $this->value;
    }

    function getValueForDb()
    {
        if ($this->value) {

            $split = explode(',',$this->value);
            $class = CrudModel :: resolveClass($this->config['model']);
            $dummyModel = new $class();
            $ids = [];
            foreach ($split as $title) {
                $title = trim($title);
                $obj = $class::firstOrCreate([$dummyModel->confParam('title_field') => $title]);
                $ids[] = $obj->id;
            }


            return $ids;
        }
    }


} 