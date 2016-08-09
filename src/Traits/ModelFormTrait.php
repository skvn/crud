<?php namespace Skvn\Crud\Traits;


use Skvn\Crud\Form\Form;
use Illuminate\Support\Str;
use Skvn\Crud\Exceptions\ConfigException;

trait ModelFormTrait
{

    public $form = null;


    function getFieldsByField()
    {
        $list = [];
        foreach ($this->config['fields'] as $fld)
        {
            $list[$fld['field']] = $fld;
        }
        return $list;
    }

    function getField($name, $throw = false)
    {
        $field = $this->config['fields'][$name] ?? [];
        if (empty($field) && $throw)
        {
            throw new ConfigException('Field ' . $name . ' on ' . $this->classShortName . ' is used in form but does not exist inside "fields"');
        }
        $field['name'] = $name;
        return $field;
    }

    protected function configureField($name, $config)
    {
        $config['name'] = $name;
        if (empty($config['field']))
        {
            $config['field'] = $name;
        }
        if (empty($config['type']))
        {
            return $config;
        }
        if (!empty($config['hint_default']) && !empty($config['hint']) &&  $config['hint'] === 'auto')
        {
            $config['hint'] = $this->classShortName.'_fields_'.$name;
        }
        return Form :: getAvailControl($config['type'])->configureModel($this, $config);
    }


    public function getForm($args = [])
    {
        if (!$this->form)
        {
            $this->form = Form :: create([
                'crudObj' => $this,
                'props' => $args
            ]);

            $form_alias = $this->scopeParam('form');
            if (!empty($form_alias)) {

                $config = $this->confParam('forms.'.$form_alias);
                if ($config) {
                    $plain = isset($config[0]);
                    foreach ($config as $idx => $fld) {
                        if ($plain) {
                            $this->form->addField($fld, $this->getField($fld, true));
                        } else {
                            $this->form->addTab($idx, array_filter($fld, function ($k) {
                                return $k != "fields";
                            }, ARRAY_FILTER_USE_KEY));
                            if (!empty($fld['fields'])) {
                                foreach ($fld['fields'] as $field) {
                                    $this->form->addField($field, $this->getField($field, true), $idx);
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($args['fillData']))
            {
                $this->form->import($args['fillData']);
            }
        }

        return $this->form;
    }

    public function setForm(Form $form)
    {
        $this->form = $form;
    }

    function formatted($col, $args = [])
    {
        $rel = $this->crudRelations->resolveReference($col);
        if ($rel !== false)
        {
            try
            {
                $relObj = $this->{$rel['rel']};
                if (!is_object($relObj))
                {
                    return "";
                }
                $value = $relObj->{$rel['attr']};
            }
            catch (\Exception $e)
            {
                return "(not found)" . $e->getMessage() . ":" . $e->getFile() . ":" . $e->getLine();
            }
        }
        elseif ($this->__isset($col))
        {
            $field = $this->getField($col);
            if (!empty($field['type']))
            {
                $control = Form :: createControl($this, $field);
                $value = $control->getOutputValue();
            }
            else
            {
                $value = $this->$col;
            }
        }
        else
        {
            return null;
        }
        if (!empty($args['formatter']))
        {
            $formatter = "crudFormatValue" . Str :: camel($args['formatter']);
            if (method_exists($this, $formatter))
            {
                $value = $this->$formatter($value, $args);
            }
        }

        return $value;
    }

    function getHiddenFields()
    {
        return [];
    }

    

}