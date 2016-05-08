<?php namespace Skvn\Crud\Traits;


use Skvn\Crud\Filter\FilterFactory;
use Skvn\Crud\Filter\Filter;


trait ModelFilterTrait
{

    protected $filterObj;


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
        $filter =  FilterFactory::create($this, $this->getScope());
        $this->setFilter($filter);
    }

    public function setFilter(Filter $filterObj)
    {
        $this->filterObj = $filterObj;
        $this->filterObj->setModel($this);
    }

    public  function getFilter()
    {
        if (!$this->filterObj)
        {
            throw new \InvalidArgumentException("Filter object is not set");
        }
        return $this->filterObj;
    }

    public function getFilterColumns()
    {
        return $this->filterObj->filters;
    }

    public function fillFilter($scope, $input)
    {
        $this->initFilter($scope);

        return $this->filterObj->fill($input, true);
    }


    function getListDefaultFilter()
    {
        if (!empty($this->config['list'][$this->scope]['filter_default']))
        {
            return $this->config['list'][$this->scope]['filter_default'];
        }

        return [];
    }

}