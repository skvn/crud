<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Skvn\Crud\Contracts\FormControl;


class TextArea extends Field implements WizardableField, FormControl{

    
    use WizardCommonFieldTrait;
    

    function controlType()
    {
        return "textarea";
    }


    public function wizardDbType() {
        return 'longText';
    }

    function controlTemplate()
    {
        return "crud::crud/fields/textarea.twig";
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/textarea.twig";
    }

    function controlWidgetUrl()
    {
        return "js/widgets/editor.js";
    }

    function wizardCaption()
    {
        return "Textarea";
    }




    function getValue()
    {
        if (!$this->value)
        {
            $this->value = $this->model->getAttribute($this->getField());
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        if (!empty($this->value)) {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->field;
            return ['cond' => [$col, 'LIKE', '%' . $this->value . '%']];
        }

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

