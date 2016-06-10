<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\CommonFieldWizardTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;

class TextArea extends Field implements WizardableField{

    
    use CommonFieldWizardTrait;
    
    const TYPE = "textarea";



    public static function fieldDbType() {
        return 'longText';
    }

    static function controlTemplate()
    {
        return "crud::crud/fields/textarea.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/textarea.twig";
    }

    static function controlWidgetUrl()
    {
        return "js/widgets/editor.js";
    }

    static function controlCaption()
    {
        return "Textarea";
    }

    static function controlFiltrable()
    {
        return false;
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

    public  static function callbackModelConfig($fieldKey,array &$modelConfig,CrudModelPrototype $modelPrototype)
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

