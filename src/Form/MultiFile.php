<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class MultiFile extends Field implements WizardableField, FormControl{


    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->name);
        return $this;
    }

    function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }

    function pullFromData(array $data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }

    function controlType():string
    {
        return "multi_file";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.multi_file";
    }

    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.multi_file";
    }


    function wizardCaption()
    {
        return "Multiple files";
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
     * Return an array of relations for which the control can be used
     *
     * @return array
     */
    public function wizardIsForRelations():array {

        return [
            'hasManyFiles',
        ];
    }

    function getExisting()
    {
        return $this->model->getAttribute($this->getName());
    }


} 