<?php  namespace Skvn\Crud\Traits;



trait WizardCommonFieldTrait
{


    /**
     * Returns database column type for the field
     *
     * @return string
     */
    public function wizardDbType() {
        return 'string';
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
        ];
    }

    /**
     * Returns true if the  control can be used  for "many" - type relation editing
     *
     * @return bool
     */
    public function wizardIsForManyRelation():bool
    {
        return false;
    }

    function wizardCaption()
    {
        return "---";
    }

    public function wizardCallbackFieldConfig (&$fieldKey,array &$fieldConfig,   $modelPrototype)
    {
        
    }


    public function wizardCallbackModelConfig($fieldKey,  array &$modelConfig,  $modelPrototype)
    {

    }
    

}