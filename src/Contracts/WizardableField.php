<?php namespace Skvn\Crud\Contracts;




interface WizardableField
{



    /**
     * Returns database column type for the field
     *
     * @return null|string
     */
    public function wizardDbType();
    
    /**
     * Returns true if the  control can be used only for relation editing only
     * 
     * @return bool
     */
    public function wizardIsForRelationOnly():bool;


    /**
     * Return an array of relations for which the control can be used
     *
     * @return array
     */
    public function wizardIsForRelations():array;

    
    /**
     * Get path to wizard template
     *
     * @return string
     */
    public function wizardTemplate() ;

    function wizardCaption();

    /**
     * Apply actions to the field config array during setup in wizard
     *
     * @param strinf $fieldKey
     * @param array $fieldConfig
     * @param  $modelPrototype
     * @return void
     */
    public function wizardCallbackFieldConfig (&$fieldKey,array &$fieldConfig, $modelPrototype);

    /**
     * Apply actions to the model config array during setup in wizard
     *
     * @param strinf $fieldKey
     * @param array $modelConfig     
     * @param  $modelPrototype
     * @return void
     */
    public function wizardCallbackModelConfig($fieldKey,array &$modelConfig, $modelPrototype);

}