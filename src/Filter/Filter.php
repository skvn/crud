<?php namespace Skvn\Crud\Filter;


use Skvn\Crud\Models\CrudModel;
use Skvn\Crud\Form\Form;

class Filter {


    static  $instances = [];

    public $filters;
    protected $model, $crudObj,$form, $session, $defaults;

    public function __construct(CrudModel $model)
    {
        $this->session = app()['session'];
        $this->setModel($model);
    }

    public static  function create(CrudModel $model, $scope)
    {
        $key = $model->classShortName . "_" . $scope;
        //$context = implode(':',$context_params);
        if (empty(self :: $instances[$key]))
        {
            $instances[$key] =  new Filter($model, $scope);
        }

        return $instances[$key];
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

        }
        else
        {
            $this->fill($this->crudObj->getListDefaultFilter());
        }
    }

    public function initFilterColumns()
    {
        $filters = $this->crudObj->getFilterConfig();
        $this->defaults = $this->crudObj->getListDefaultFilter();

        if ($filters)
        {
            //SUPPORT FOR OLD STYLE 
            //when filter field is described in 'fields'
            // and filter contains only field names
            if (isset($filters[0]))
            {
                foreach ($filters as $column_name)
                {
                    if ($field_description = $this->initOneFilterColumn($column_name))
                    {
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
        if ($field_description = $this->crudObj->getColumn($column_name))
        {
            $field_description['required'] = 0;
            if ($field_description['type'] == Form::FIELD_SELECT)
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

    public function fill($input, $andStore=false)
    {
        $storeData = [];
        if ($this->filters  && is_array($this->filters ))
        {
            $form = $this->getForm($input, true);
            foreach ($this->filters as $k => $filterCol)
            {
                $value = $form->fields[$k]->getValue();
                $storeData[$k] = $value;
                $this->filters[$k]['value'] = $value;
            }
        }

        if ($andStore)
        {
            $this->store($storeData);
        }
    }

    public function store($data)
    {
        $this->session->put($this->getStorageKey(),$data);
    }

    public function getStorageKey()
    {
        return 'crud_filter_'.$this->crudObj->classViewName . "_" . $this->crudObj->scope;
    }

    public function getForm($fillData=null, $renew=false)
    {
        if ($this->filters)
        {
            if (!$this->form || $renew)
            {
                $this->form = new Form($this->crudObj, $this->filters, $fillData);
            }
        }

        return $this->form;
    }

    public function getConditions()
    {
        $filters = [];
        if ($this->filters)
        {
            $form = $this->getForm();
            foreach ($form->fields as $field)
            {
                $c = $field->getFilterCondition();
                if ($c)
                {
                    $filters[$field->getName()] = $c;
                }
            }
        }
        return $filters;
    }

}