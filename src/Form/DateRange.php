<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\CommonFieldWizardTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;

class DateRange extends Range implements WizardableField 
{

    use CommonFieldWizardTrait;
    
    const TYPE = "date_range";


    public static function fieldDbType() {
        return '';
    }

    static function controlTemplate()
    {
        return "crud::crud/fields/date_range.twig";
    }

    static function controlWizardTemplate()
    {
        return "crud::wizard/blocks/fields/date_range.twig";
    }

    static function controlWidgetUrl()
    {
        return false;
    }

    static function controlCaption()
    {
        return "Date range";
    }

    static function controlFiltrable()
    {
        return true;
    }


    function getValueFrom()
    {
        if ($this->getValue())
        {
            $from =  explode('~',$this->getValue())[0];
            if (!empty($from))
            {
                return date($this->config['format'],$from);
            }
        }
    }

    function getValueTo()
    {

        if ($this->getValue())
        {
            $spl = explode('~',$this->getValue());
            if (!empty($spl[1]))
            {

                return date($this->config['format'],$spl[1]);
            }
        }
    }

    function getValueForList()
    {
        return $this->getValueFrom() . "-" . $this->getValueTo();
    }

    function prepareValueForDb($value)
    {
        if (is_numeric($value))
        {
            return $value;
        }
        return strtotime($value . ' 14:23');
    }

//    private function isInt()
//    {
//        return (empty($this->config['db_type']) ||$this->config['db_type'] == 'int');
//    }


//    function  getValueForDb()
//    {
//        if ($this->isInt())
//        {
//            return strtotime($this->getValue() . ' 14:23');
//        }
//        else
//        {
//            return date('Y-m-d',strtotime($this->getValue()));
//        }
//    }

    function importValue($data)
    {
        if (!empty($data[$this->name]) && strpos($data[$this->name],'~') !== false)
        {
            $this->setValue($data[$this->name]);
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
                $this->setValue($from . '~' . $to);
            }
        }

    }

    public static function callbackFieldConfig($fieldKey, array &$fieldConfig,  CrudModelPrototype $modelPrototype)
    {
        $fieldConfig['db_type'] = $modelPrototype->column_types[$fieldConfig['fields'][0]];

        $formats = $modelPrototype->wizard->getAvailableDateFormats();
        $fieldConfig['jsformat'] = $formats[$fieldConfig['format']]['js'];
        $fieldConfig['format'] = $formats[$fieldConfig['format']]['php'];
        unset($fieldConfig['property_name']);

    }



//



} 