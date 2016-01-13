<?php namespace Skvn\Crud\Filter;

use Skvn\Crud\Models\CrudModel;

class FilterFactory
{
    static  $instances = [];



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

} 