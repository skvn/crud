<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Contracts\FormControlFilterable;
use Skvn\Crud\Traits\FormControlCommonTrait;


class TextArea extends Field implements WizardableField, FormControl, FormControlFilterable{

    
    use WizardCommonFieldTrait;
    use FormControlCommonTrait;


    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            return ['cond' => [$this->getFilterColumnName(), 'LIKE', '%' . $this->value . '%']];
        }

    }


    function controlType():string
    {
        return "textarea";
    }

    function controlTemplate():string
    {
        return "crud::crud/fields/textarea.twig";
    }

    function controlWidgetUrl():string
    {
        return "js/widgets/editor.js";
    }


    public function wizardDbType()
    {
        return 'longText';
    }


    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/textarea.twig";
    }


    function wizardCaption()
    {
        return "Textarea";
    }






    public  function wizardCallbackModelConfig($fieldKey,array &$modelConfig,CrudModelPrototype $modelPrototype)
    {
        if (!empty($modelConfig['fields'][$fieldKey]['editor']))
        {
            if (!isset($modelConfig['inline_img']))
            {
                $modelConfig['inline_img'] = [];
                if (!isset($modelConfig['traits']))
                {
                    $modelConfig['traits'] = [];
                }
                $modelConfig['traits'][] = 'ModelInlineImgTrait';
            }

            $modelConfig['inline_img'][] = $fieldKey;
        }
    }



    }

