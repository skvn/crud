<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Skvn\Crud\Contracts\FormControl;


class DateRange extends Range implements WizardableField, FormControl
{
    use WizardCommonFieldTrait;


    function getOutputValue():string
    {
        return date($this->config['format'], $this->getValueFrom()) . "-" . date($this->config['format'], $this->getValueTo());
    }

    function pullFromData(array $data)
    {
        if (!empty($data[$this->name]) && strpos($data[$this->name],'~') !== false)
        {
            $this->value = $data[$this->name];
        }
        else
        {
            if (isset($data[$this->getFromFieldName()]) || isset ($data[$this->getToFieldName()]))
            {
                $from = 0;
                $to = '';
                if (isset($data[$this->getFromFieldName()]))
                {
                    $from = strtotime($data[$this->getFromFieldName()]);
                }
                if (isset($data[$this->getToFieldName()]))
                {
                    $to = strtotime($data[$this->getToFieldName()]);
                }
                $this->value = $from . '~' . $to;
            }
        }

    }



    function controlType():string
    {
        return "date_range";
    }


    function controlTemplate():string
    {
        return "crud::crud/fields/date_range.twig";
    }

    public function wizardDbType()
    {
        return '';
    }

    function wizardTemplate()
    {
        return "crud::wizard/blocks/fields/date_range.twig";
    }

    function wizardCaption()
    {
        return "Date range";
    }







    public function wizardCallbackFieldConfig($fieldKey, array &$fieldConfig,  CrudModelPrototype $modelPrototype)
    {
        $fieldConfig['db_type'] = $modelPrototype->column_types[$fieldConfig['fields'][0]];

        $formats = $modelPrototype->wizard->getAvailableDateFormats();
        $fieldConfig['jsformat'] = $formats[$fieldConfig['format']]['js'];
        $fieldConfig['format'] = $formats[$fieldConfig['format']]['php'];
        unset($fieldConfig['property_name']);

    }



//



} 