<?php namespace Skvn\Crud\Traits;

trait ModelFilterTrait
{
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
    }

    public function setFilter(Filter $filterObj)
    {
        return $this->getList()->setFilter($filterObj);
    }

    public  function getFilter()
    {
        return $this->getList()->getFilter();
    }

    public function getFilterColumns()
    {
        return $this->getList()->getFilterColumns();
    }

    public function fillFilter($scope, $input)
    {
        return $this->getList()->fillFilter($scope, $input);
    }


    function getListDefaultFilter()
    {
        return $this->getList()->getDefaultFilter();
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