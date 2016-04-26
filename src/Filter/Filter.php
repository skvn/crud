<?php namespace Skvn\Crud\Filter;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Form\Form;

class Filter {



    public $filters;
    protected $model, $crudObj,$scope, $form, $session, $defaults;

    public function __construct(CrudModel $model, $scope)
    {
        $this->session = app()['session'];
        $this->scope = $scope;
        $this->setModel($model);
    }

    public function setModel(CrudModel $crudObj)
    {
        $this->model = $crudObj->classShortName;
        $this->crudObj = $crudObj;
        $this->initFilterColumns();
        $this->fillFromStorage();
    }

    public function fillFromStorage()
    {

        if ($this->session->has($this->getStorageKey()))
        {

            $this->fill($this->session->get($this->getStorageKey()));

        } else
        {
            $this->fill($this->crudObj->config->getListDefaultFilter());
        }
    }

    public function initFilterColumns()
    {
        $filters = $this->crudObj->config->getFilter();
        $this->defaults = $this->crudObj->config->getListDefaultFilter();

        if ($filters)
        {
            //SUPPORT FOR OLD STYLE 
            //when filter field is described in 'fields'
            // and filter contains only field names
            if (isset($filters[0])) {
                foreach ($filters as $column_name) {
                    if ($field_description = $this->initOneFilterColumn($column_name)) {
                        $this->filters[$column_name] = $field_description;
                    }
                }
            }
//            } else {
//                foreach ($filters as $column_name=>$field_description) {
//                    if (is_array($field_description)) {
//                        $this->appendColumnDefaults($column_name, $field_description);
//                        $this->filters[$column_name] = $field_description;
//                    } else {
//                        if ($field_description = $this->initOneFilterColumn($column_name)) {
//                            $this->filters[$column_name] = $field_description;
//                        }
//                    }
//                }
//            }

        }
    }

    public function initOneFilterColumn($column_name)
    {
        if ($field_description = $this->crudObj->config->getColumn($column_name))
        {
            $field_description['required'] = 0;
            if ($field_description['type'] == \Skvn\Crud\CrudConfig::FIELD_SELECT)
            {
                $field_description['multiple'] = 1;
            }

            $this->appendColumnDefaults($column_name, $field_description);

            return $field_description;
        }

    }
    
    private function appendColumnDefaults($column_name, $field_description)
    {
        if (!empty($this->defaults[$column_name]))
        {
            $field_description['default'] = (is_array($this->defaults[$column_name])?implode(',',$this->defaults[$column_name]):$this->defaults[$column_name]);
        }

        return $field_description;
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function fill($input, $andStore=false)
    {

        $storeData = [];
        if ($this->filters  && is_array($this->filters ))
        {


            $form = $this->getForm($input, true);

            foreach ($this->filters as $k => $filterCol) {


//                if (array_key_exists($k, $input)) {

                $value = $form->fields[$k]->getValue();
                $storeData[$k] = $value;
                $this->filters[$k]['value'] = $value;


//                } else {
//                    $this->filters[$k]['value'] = null;
//                }
            }
        }


        if ($andStore) {
            $this->store($storeData);
        }

    }

    public function store($data)
    {


        $this->session->put($this->getStorageKey(),$data);
    }

    public function getStorageKey()
    {

        return 'crud_filter_'.$this->getScope();
    }

    public function getForm($fillData=null, $renew=false)
    {
        if ($this->filters)
        {
            if (!$this->form || $renew) {
                $this->form = new Form($this->crudObj, $this->filters, $fillData);
            }
        }

        return $this->form;
    }

    public function getConditions()
    {
        $filters = [];
        if ($this->filters) {
            $form = $this->getForm();
            foreach ($form->fields as $field) {
                $filters[$field->getName()] = $field->getFilterCondition();
            }


        }
        return $filters;

    }

}