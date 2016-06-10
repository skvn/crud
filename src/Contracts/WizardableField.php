<?php namespace Skvn\Crud\Contracts;


use Skvn\Crud\Wizard\CrudModelPrototype;
use Skvn\Crud\Wizard\Wizard;

interface WizardableField
{

    const WIZARDABLE = true;


    /**
     * Returns database column type for the field
     *
     * @return null|string
     */
    public static function fieldDbType();
    
    /**
     * Returns true if the  control can be used only for relation editing only
     * 
     * @return bool
     */
    public static function controlIsForRelationOnly():bool;

    /**
     * Returns true if the  control can be used only for relation editing
     *
     * @return bool
     */
    public static function controlIsForRelation():bool;


    /**
     * Returns true if the  control can be used for "many" - type relation editing
     *
     * @return bool
     */
    public static function controlIsForManyRelation():bool;
    
    /**
     * Get path to wizard template
     *
     * @return string
     */
    public static function controlWizardTemplate() ;

    /**
     * Apply actions to the field config array during setup in wizard
     *
     * @param strinf $fieldKey
     * @param array $fieldConfig
     * @param CrudModelPrototype $modelPrototype
     * @return void
     */
    public static function callbackFieldConfig ($fieldKey,array &$fieldConfig,CrudModelPrototype $modelPrototype);

    /**
     * Apply actions to the model config array during setup in wizard
     *
     * @param strinf $fieldKey
     * @param array $modelConfig     
     * @param CrudModelPrototype $modelPrototype
     * @return void
     */
    public  static function callbackModelConfig($fieldKey,array &$modelConfig,CrudModelPrototype $modelPrototype);

}