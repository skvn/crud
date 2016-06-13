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