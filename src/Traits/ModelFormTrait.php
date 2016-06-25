<?php namespace Skvn\Crud\Traits;


use Skvn\Crud\Form\Form;

trait ModelFormTrait
{

    //protected $form_fields_collection;
    public $form = null;


    /**
     * Add field on the fly to the 'fields' array
     *
     * @param $fieldName
     * @param $fieldLabel
     * @param $fieldType
     * @param $fieldConfig
     */
//    public function addFormField($fieldName,$fieldLabel,  $fieldType=null, $fieldConfig=[]){
//
//        if (empty($this->config['fields']))
//        {
//            $this->config['fields'] = [];
//        }
//
//        if (empty($this->config['form']))
//        {
//            $this->config['form'] = [];
//        }
//
//        if (empty($fieldType))
//        {
//            $fieldType = "text";
//        }
//        $params = ['type'=>$fieldType];
//
//        if (!empty($fieldLabel))
//        {
//            $params['title'] = $fieldLabel;
//        }
//        $this->config['fields'][$fieldName] = $params + $fieldConfig;
//        $this->config['form'][] = $fieldName;
//    }


    /**
     * Return one form field object by name
     *
     * @param $name
     */
//    public  function getFormField($name)
//    {
//        return $this->getFieldsObjects(null)[$name];
//    }

    
    public function getForm($args = [])
    {
        if (!$this->form)
        {
            $this->form = Form :: create([
                'crudObj' => $this,
                'props' => $args
            ]);

            $config = $this->scopeParam('form');
            $plain = isset($config[0]);
            foreach ($config as $idx => $fld)
            {
                if ($plain)
                {
                    $this->form->addField($fld, $this->getField($fld, true));
                }
                else
                {
                    $this->form->addTab($idx, array_filter($fld, function($k){return $k != "fields";}, ARRAY_FILTER_USE_KEY));
                    if (!empty($fld['fields']))
                    {
                        foreach ($fld['fields'] as $field)
                        {
                            $this->form->addField($field, $this->getField($field, true), $idx);
                        }
                    }
                }
            }

            if (!empty($args['fillData']))
            {
                $this->form->import($args['fillData']);
            }

//            $this->form = Form :: create([
//                'crudObj' => $this,
//                'fields' => $formConfig['fields'],
//                'tabs' => $formConfig['tabs'],
//                'data' => !empty($config['fillData'])?$config['fillData']:null,
//                'props' => $config
//            ]);
        }

        return $this->form;
    }

    public function setForm(Form $form)
    {
        $this->form = $form;
    }

//    public function getFormConfig()
//    {
//        $form =  $this->getListConfig('form');
//        $tabbed = !isset($form[0]);
//        $form_array = [];
//        $tab_array = [];
//
//        if (is_array($form))
//        {
//            if ($tabbed)
//            {
//                foreach ($form as $tab_alias=>$tab)
//                {
//                    if (is_array($tab['fields']))
//                    {
//                        if (!isset($tab_array[$tab_alias]))
//                        {
//                            $tab_array[$tab_alias] = $tab;
//                            unset($tab_array[$tab_alias]['fields']);
//                        }
//                        foreach ($tab['fields'] as $fname)
//                        {
//                            $form_array[$fname] = $this->getField($fname);
//                            $form_array[$fname]['tab'] = $tab_alias;
//                        }
//                    }
//                }
//            } else {
//
//                foreach ($form as $fname) {
//                    $form_array[$fname] = $this->getField($fname);
//                }
//            }
//        }
//
//        return [
//            'fields'=>$form_array,
//            'tabs'=>$tab_array
//        ];
//    }


     
//    public function getFieldsObjects($fillData=null)
//    {
//        if (!$this->form_fields_collection)
//        {
//            $form = Form :: create([
//                'crudObj' => $this,
//                'fields' => $this->getFields(),
//                'data' => $fillData
//            ]);
//            $this->form_fields_collection = $form->fields;
//        }
//
//        return $this->form_fields_collection;
//    }

    

}