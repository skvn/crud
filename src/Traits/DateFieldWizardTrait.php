<?php  namespace Skvn\Crud\Traits;

trait DateFieldWizardTrait
{


    /**
     * Apply actions to the field config array during setup in wizard
     *
     * @param array $fieldConfig
     * @return void
     */
    static function callbackFieldConfig (array &$fieldConfig)
    {
        
    }

    /**
     * Apply actions to the model config array during setup in wizard
     *
     * @param array $modelConfig
     * @return void
     */
    static function callbackModelConfig( array &$modelConfig)
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