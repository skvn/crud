<?php namespace Skvn\Crud\Traits;


//use Skvn\Crud\Filter\Filter;


trait ModelFilterTrait
{

//    protected $filterObj;


    public  function appendConditions($conditions)
    {
        return $conditions;
    }

    public  function preApplyConditions($coll, $conditions)
    {
        return $conditions;
    }

    public function initFilter()
    {
        return $this->getList()->initFilter();
//        $filter =  Filter::create($this, $this->getScope());
//        $this->setFilter($filter);
    }

    public function setFilter(Filter $filterObj)
    {
        return $this->getList()->setFilter($filterObj);
//        $this->filterObj = $filterObj;
//        $this->filterObj->setModel($this);
    }

    public  function getFilter()
    {
        return $this->getList()->getFilter();
//        if (!$this->filterObj)
//        {
//            throw new \InvalidArgumentException("Filter object is not set");
//        }
//        return $this->filterObj;
    }

    public function getFilterColumns()
    {
        return $this->getList()->getFilterColumns();
//        return $this->filterObj->filters;
    }

    public function fillFilter($scope, $input)
    {
        return $this->getList()->fillFilter($scope, $input);
//        $this->initFilter($scope);
//
//        return $this->filterObj->fill($input, true);
    }


    function getListDefaultFilter()
    {
        return $this->getList()->getDefaultFilter();
//        if (!empty($this->config['list'][$this->scope]['filter_default']))
//        {
//            return $this->config['list'][$this->scope]['filter_default'];
//        }
//
//        return [];
    }

    function getAutocompleteList($query)
    {
        if (empty($this->config['title_field']))
        {
            throw new CrudException('Unable to init AutocompleteList: title_field is not configured');
        }

        if (!empty($query))
        {
            return self::where($this->config['title_field'], 'LIKE', $query . '%')
                ->pluck($this->config['title_field']);
        }

        return [];
    }


}