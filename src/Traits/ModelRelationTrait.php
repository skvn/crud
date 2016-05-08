<?php namespace Skvn\Crud\Traits;


trait ModelRelationTrait
{

    function getRelationNameByColumnName($colName)
    {
        $col = $this->getFields($colName);
        if (!empty($col['relation_name']))
        {
            return $col['relation_name'];
        }

        return $colName;
    }//

    protected   function resolveColumnByRelationName($col, $scope='fields')
    {
        foreach ($this->config[$scope] as $col_name => $desc)
        {
            if (!empty($desc['relation_name']) &&  $desc['relation_name'] == $col)
            {
                $desc['column_index'] = $col_name;
                return $desc;
            }
        }

    }

    function resolveListRelation($alias)
    {
        if (strpos($alias,'::') !== false)
        {
            return explode('::',$alias);
        }
        return false;
    }


}