<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Models\CrudModelCollectionBuilder;
use Illuminate\Support\Collection;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class Radio extends Select
{
    


    function controlType():string
    {
        return "radio";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.radio";
    }

    function controlWidgetUrl():string
    {
        return "";
    }


    /**
     * Returns true if the  control can be used only for relation editing only
     *
     * @return bool
     */
    public function wizardIsForRelationOnly():bool
    {
        return false;
    }


    /**
     * Return an array of relations for which the control can be used
     *
     * @return array
     */
    public function wizardIsForRelations():array {

        return [
            'hasOne',
            'belongsTo',
        ];
    }


    public function wizardDbType()
    {
        return '';
    }


    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.radio";
    }


    function wizardCaption()
    {
        return "Radio group";
    }
    
} 