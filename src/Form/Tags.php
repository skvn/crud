<?php namespace Skvn\Crud\Form;

use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Tags extends Field implements WizardableField, FormControl {

    
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;


    function pullFromModel()
    {
        $class = CrudModel :: resolveClass($this->config['model']);
        $dummyModel = new $class();
        $ids = $this->model->getRelationIds($this->name);
        if (count($ids))
        {
            $collection = $class::findMany($ids);
            $this->value = $collection->pluck($dummyModel->confParam('title_field'));
        }
    }

    function pullFromData(array $data)
    {
        if (!empty($data[$this->name]))
        {
            if (is_array($data[$this->name]))
            {
                $this->value = $data[$this->name];
            }
            else
            {
                $this->value = explode(",", $data[$this->name]);
            }
        }
        else
        {
            $this->value = [];
        }
    }

    function pushToModel()
    {
        $ids = [];
        $class = CrudModel :: resolveClass($this->config['model']);
        $dummyModel = new $class();
        if (!empty($this->value))
        {
            foreach ($this->value as $title)
            {
                $obj = $class::firstOrCreate([$dummyModel->confParam('title_field') => trim($title)]);
                $ids[] = $obj->getKey();
            }
        }
        $this->model->setAttribute($this->name, $ids);
    }


    function controlType():string
    {
        return "tags";
    }

    function controlTemplate():string
    {
        return "crud::crud/fields/tags.twig";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/tags.js";
    }

    /**
     * Returns true if the  control can be used only for relation editing only
     *
     * @return bool
     */
    public function wizardIsForRelationOnly():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used only for relation editing
     *
     * @return bool
     */
    public function wizardIsForRelation():bool
    {
        return true;
    }

    /**
     * Returns true if the  control can be used  for "many" - type relation editing
     *
     * @return bool
     */
    public function wizardIsForManyRelation():bool
    {
        return true;
    }
    

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/tags.twig";
    }



} 