<?php namespace Skvn\Crud\Traits;


use Skvn\Crud\Form\Form;

trait ModelFormTrait
{

    protected $form_fields_collection;
    public $form;


    /**
     * Add field on the fly to the 'fields' array
     *
     * @param $fieldName
     * @param $fieldLabel
     * @param $fieldType
     * @param $fieldConfig
     */
    public function addFormField($fieldName,$fieldLabel,  $fieldType=null, $fieldConfig=[]){

        if (empty($this->config['fields']))
        {
            $this->config['fields'] = [];
        }

        if (empty($this->config['form']))
        {
            $this->config['form'] = [];
        }

        if (empty($fieldType))
        {
            $fieldType = Form::FIELD_TEXT;
        }
        $params = ['type'=>$fieldType];

        if (!empty($fieldLabel))
        {
            $params['title'] = $fieldLabel;
        }
        $this->config['fields'][$fieldName] = $params + $fieldConfig;
        $this->config['form'][] = $fieldName;
        //$this->markFillable($fieldName);

//        if (!empty($fieldConfig['fields']))
//        {
//            foreach ($fieldConfig['fields'] as $f)
//            {
//                $this->markFillable($f);
//            }
//        }
    }


    /**
     * Return one form field object by name
     *
     * @param $name
     */
    public  function getFormField($name)
    {
        return $this->getForm()->getFieldByName($name);
    }

    
    public function getForm($config = [])
    {
        if (!empty($config['forceNew']) || !$this->form)
        {
            //$this->form = new Form($this,$this->getFormConfig(), !empty($config['fillData'])?$config['fillData']:null, $config);
            $this->form = Form :: create([
                'crudObj' => $this,
                'config' => $this->getFormConfig(),
                'tabs' => $this->confParam('tabs'),
                'data' => !empty($config['fillData'])?$config['fillData']:null,
                'props' => $config
            ]);
        }

        return $this->form;
    }

    public function setForm(Form $form)
    {
        $this->form = $form;
    }

     
    public function getFieldsObjects($fillData=null)
    {
        if (!$this->form_fields_collection)
        {
            //$form = new Form($this, $this->getFields(), $fillData);
            $form = Form :: create([
                'crudObj' => $this,
                'config' => $this->getFields(),
                'data' => $fillData
            ]);
            $this->form_fields_collection = $form->fields;
        }

        return $this->form_fields_collection;
    }

    

}