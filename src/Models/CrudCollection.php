<?php namespace Skvn\Crud\Models;

use Illuminate\Database\Eloquent\Collection;

class CrudCollection extends Collection
{
    public function titles()
    {
        return array_map(function ($m) {
            return $m->getTitle();
        }, $this->items);
    }

}