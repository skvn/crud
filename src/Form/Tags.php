<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Traits\CommonFieldWizardTrait;

class Tags extends Field implements WizardableField {

    
    use CommonFieldWizardTrait;
    
    const TYPE = "tags";


        
    /**
     * Returns true if the  control can be used only for relation editing only
     *
     * @return bool
     */
    public static function controlIsForRelationOnly():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used only for relation editing
     *
     * @return bool
     */
    public static function controlIsForRelation():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used  for "many" - type relation editing
     *
     * @return bool
     */
    public static function controlIsForManyRelation():bool
    {
        return true;
    }
    
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