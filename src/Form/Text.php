<?php namespace Skvn\Crud\Form;


class Text extends Field {

    const TYPE = "text";



    static function controlTemplate()
    {
        return "crud::crud/fields/text.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/text.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "Text input";
    }

    static function controlFiltrable()
    {
        return true;
    }


    function getValue()
    {
        if (is_null($this->value))
        {
            if (!in_array($this->getName(), $this->model->getHidden()))
            {
                $this->value = $this->model->getAttribute($this->getField());
            }
        }

        return $this->value;
    }

    function getFilterCondition()
    {
        if (!empty($this->value))
        {
            $col = !empty($this->config['filter_column']) ? $this->config['filter_column'] : $this->field;
            $value = str_replace(['*', '?'], ['%', '_'], $this->value);
            if (strpos($value, "~") === 0)
            {
                return ['cond' => [$col, 'NOT LIKE',  substr($value, 1) ]];
            }
            else
            {
                return ['cond' => [$col, 'LIKE',  $value ]];
            }
        }


    }
} 