<?php namespace Skvn\Crud\Form;


class TextArea extends Field {

    const TYPE = "textarea";

    static $controlInfo = [
        'caption' => 'Textarea',
    ];

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
} 