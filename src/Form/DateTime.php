<?php namespace Skvn\Crud\Form;


use Skvn\Crud\Contracts\WizardableField;
use Skvn\Crud\Traits\WizardCommonFieldTrait;
use Skvn\Crud\Wizard\CrudModelPrototype;
use Skvn\Crud\Contracts\FormControl;
use Skvn\Crud\Traits\FormControlCommonTrait;


class DateTime extends Field implements WizardableField, FormControl
{

    use WizardCommonFieldTrait;
    use FormControlCommonTrait;

    function pullFromModel()
    {
        $this->value = $this->model->getAttribute($this->field);
        if (!$this->value)
        {
            if ($this->isInt())
            {
                $this->value = time();
            }
            else
            {
                $this->value = new \DateTime('now');
            }
        }
    }

    function getOutputValue():string
    {

        if ($this->isInt())
        {
            return date($this->config['format'], $this->value);
        }
        else
        {
            return date($this->config['format'], strtotime($this->value));
        }
    }

    function pullFromData(array $data)
    {
        if (isset($data[$this->field]))
        {
            if ($this->isInt())
            {
                $this->value = is_numeric($data[$this->field]) ? $data[$this->field] : strtotime($data[$this->field]);
            }
            else
            {
                $this->value = $data[$this->field];
            }
        }
        else
        {
            $this->value = null;
        }
    }



    function controlType():string
    {
        return "date_time";
    }

    function controlTemplate():string
    {
        return "crud::crud.fields.date_time";
    }

    function controlValidateConfig():bool
    {
        return !empty($this->config['format']);
    }

    public function wizardDbType()
    {
        return 'dateTime';
    }

    function wizardTemplate()
    {
        return "crud::wizard.blocks.fields.date_time";
    }

    function wizardCaption()
    {
        return "Date + Time";
    }

    function wizardCallbackFieldConfig(&$fieldKey, array &$fieldConfig,  CrudModelPrototype $modelPrototype)
    {
        $formats = $modelPrototype->wizard->getAvailableDateTimeFormats();
        $fieldConfig['jsformat'] = $formats[$fieldConfig['format']]['js'];
        $fieldConfig['format'] = $formats[$fieldConfig['format']]['php'];
        $fieldConfig['db_type'] = $modelPrototype->column_types[$fieldKey];
    }

    private function isInt()
    {
        return (empty($this->config['db_type']) || $this->config['db_type'] == 'int');
    }

} 