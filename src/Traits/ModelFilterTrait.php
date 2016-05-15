<?php namespace Skvn\Crud\Traits;


use Skvn\Crud\Filter\FilterFactory;
use Skvn\Crud\Filter\Filter;
use Skvn\Crud\Models\CrudModelCollectionBuilder;


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