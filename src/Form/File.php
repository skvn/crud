<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class File extends Field implements WizardableField, FormControl
{

    use WizardCommonFieldTrait;
    use FormControlCommonTrait;


    function pullFromModel()
    {
        if ($this->model->getAttribute($this->field))
        {
            $this->value = $this->model->getAttribute($this->name);
        }
    }

    function pullFromData(array $data)
    {
        $this->value = isset($data[$this->name]) ? $data[$this->name] : null;
    }

    function pushToModel()
    {
        $this->model->setAttribute($this->name, $this->value);
    }



    function controlType():string
    {
        return "file";
    }
    
    function controlTemplate():string
    {
        return "crud::crud.fields.file";
    }

    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.file";
    }


    function wizardCaption()
    {
        return "File";
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
            'hasFile',
        ];
    }



}