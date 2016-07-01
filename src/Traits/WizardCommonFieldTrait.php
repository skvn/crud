<?php  namespace Skvn\Crud\Traits;



trait WizardCommonFieldTrait
{


    /**
     * Returns database column type for the field
     *
     * @return string
     */
    public function wizardDbType() {
        return 'string';
    }


    /**
     * Returns true if the  control can be used only for relation editing only
     *
     * @return bool
     */
    public function wizardIsForRelationOnly():bool
    {
        return false;
    }

    /**
     * Returns true if the  control can be used only for relation editing
     *
     * @return bool
     */
    public function wizardIsForRelation():bool
    {
        return false;
    }

    /**
     * Returns true if the  control can be used  for "many" - type relation editing
     *
     * @return bool
     */
    public function wizardIsForManyRelation():bool
    {
        return false;
    }

    function wizardCaption()
    {
        return "---";
    }

    public function wizardCallbackFieldConfig (&$fieldKey,array &$fieldConfig,   $modelPrototype)
    {
        
    }


    public function wizardCallbackModelConfig($fieldKey,  array &$modelConfig,  $modelPrototype)
    {

    }
    

//    if (!empty($f['type']) && $this->wizard->isDateField($f['type']))
//    {
//        $formats = $this->wizard->getAvailableDateFormats();
//        $this->config_data['fields'][$k]['format'] = $formats[$f['format']]['php'];
//        $this->config_data['fields'][$k]['jsformat'] = $formats[$f['format']]['js'];
//
//        if (in_array($f['type'], [Field::DATE, Field::DATE_TIME]))
//        {
//            $this->config_data['fields'][$k]['db_type'] = $this->column_types[$k];
//        } elseif ($f['type'] == Field::DATE_RANGE)
//        {
//            $this->config_data['fields'][$k]['db_type'] = $this->column_types[$f['fields'][0]];
//
//            if ($f['property_name'] != $k) {
//                $fld = $this->config_data['fields'][$k];
//                $this->config_data['fields'][$f['property_name']] = $fld;
//                $fields_to_delete[] = $k;
//            }
//        }
//
//
//    }
}