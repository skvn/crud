<?php namespace Skvn\Crud\Models;

use Illuminate\Database\Eloquent\Builder;

class CrudStubModel extends CrudModel
{
    function saveRelations()
    {
        return;
    }

    function save(array $options = [])
    {
        return;
    }

    function saveOrFail(array $options = [])
    {
        return;
    }

    protected function performUpdate(Builder $query, array $options = [])
    {
        return;
    }

    public function update(array $attributes = [], array $options = [])
    {
        return;
    }

    protected function insertAndSetId(Builder $query, $attributes)
    {
        return;
    }

    protected function performInsert(Builder $query, array $options = [])
    {
        return;
    }


}