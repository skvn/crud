<?php

namespace Skvn\Crud\Models;

use Illuminate\Database\Eloquent\Builder;

class CrudStubModel extends CrudModel
{
    public function saveRelations($name = null)
    {
    }

    public function save(array $options = [])
    {
    }

    public function saveOrFail(array $options = [])
    {
    }

    protected function performUpdate(Builder $query, array $options = [])
    {
    }

    public function update(array $attributes = [], array $options = [])
    {
    }

    protected function insertAndSetId(Builder $query, $attributes)
    {
    }

    protected function performInsert(Builder $query, array $options = [])
    {
    }
}
