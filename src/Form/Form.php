<?php


namespace LaravelCrud\Form;


class Form {

    public $config;
    public $crudObj;
    public $fields;
    //public $visibleFields;

    public function __construct($crudObj,$config,$data=null)
    {
        $this->crudObj = $crudObj;
        $this->config = $config;


        if (is_array($this->config)) {

            foreach ($config as $col => $colConfig)
            {
                if (empty($colConfig['column']))
                {
                    $colConfig['column'] = $col;
                }

                $colConfig['name'] = $col;

                $this->fields[$col] = FieldFactory::create($this, $colConfig);
//                if (empty($colConfig['hidden'])) {
//                    $this->visibleFields[$col] = $this->fields[$col];
//                }

                //set default


                if ($data) {

                    switch ($colConfig['type']) {

                        case \LaravelCrud\CrudConfig::FIELD_RANGE:

                            if (!empty($data[$col]) && strpos($data[$col],'~') !== false)
                            {

                                $this->fields[$col]->setValue($data[$col]);

                            } else {

                                if (isset($data[$col . '_from']) || isset ($data[$col . '_to'])) {

                                    $from = 0;
                                    $to = 0;

                                    if (isset($data[$col . '_from'])) {
                                        $from = $data[$col . '_from'];
                                    }

                                    if (isset($data[$col . '_to'])) {
                                        $to = $data[$col . '_to'];
                                    }


                                    $this->fields[$col]->setValue($from . '~' . $to);
                                }
                            }

                            break;

                        case \LaravelCrud\CrudConfig::FIELD_DATE_RANGE:

                            if (!empty($data[$col]) && strpos($data[$col],'~') !== false)
                            {

                                $this->fields[$col]->setValue($data[$col]);

                            } else {

                                if (isset($data[$col . '_from']) || isset ($data[$col . '_to'])) {

                                    $from = 0;
                                    $to = '';
                                    if (isset($data[$col . '_from'])) {
                                        $from = strtotime($data[$col . '_from']);
                                    }

                                    if (isset($data[$col . '_to'])) {
                                        $to = strtotime($data[$col . '_to']);
                                    }

                                    $this->fields[$col]->setValue($from . '~' . $to);
                                }

                            }

                            break;

                        default:

                            if (isset($data[$col])) {
                                $this->fields[$col]->setValue($data[$col]);
                            }

                            break;
                    }
                } else {

                    if (isset($colConfig['value'])) {
                        $this->fields[$col]->setValue($colConfig['value']);
                    }
                }

            }
        }

    }


} 