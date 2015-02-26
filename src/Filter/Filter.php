<?php namespace LaravelCrud\Filter;


use Illuminate\Support\Facades\Session;
use LaravelCrud\Model\CrudModel;
use LaravelCrud\Form\Form;

class Filter {


    protected $model;
    protected $crudObj;
    public $filters;
    protected $context;
    protected $form;

    public function __construct(array $context)
    {

        $this->context = implode(':',$context);


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

        if (\Session::has($this->getStorageKey()))
        {

            $this->fill(\Session::get($this->getStorageKey()));

        } else
        {
            $this->fill($this->crudObj->config->getListDefaultFilter());
        }
    }

    public function initFilterColumns()
    {
        $filters = $this->crudObj->config->getFilter();

        if ($filters)
        {
            foreach ($filters as $columnName)
            {

                 $this->initOneFilterColumn($columnName);

            }
        }
    }

    public function initOneFilterColumn($columnName)
    {
        if ($fieldDescription = $this->crudObj->config->getColumn($columnName))
        {
            $fieldDescription['required'] = 0;
            if ($fieldDescription['type'] == \LaravelCrud\CrudConfig::FIELD_SELECT)
            {
                $fieldDescription['multiple'] = 1;
            }
            $this->filters[$columnName] = $fieldDescription;
        }

    }

    public function getContext()
    {
        return $this->context;
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


        \Session::put($this->getStorageKey(),$data);
    }

    public function getStorageKey()
    {

        return 'crud_filter_'.$this->getContext();
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