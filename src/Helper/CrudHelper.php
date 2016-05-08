<?php namespace Skvn\Crud\Helper;

use Skvn\Crud\Models\CrudModel;

class CrudHelper {

    protected $app;


    function __construct(\Illuminate\Foundation\Application $app)
    {
        $this->app = $app;
    }

    private function flattenKids(& $tree, $node )
    {
        $tree[$node->id] = $node;
        if (is_array($node->kids))
        {
            foreach ($node->kids as $kid)
            {
                $this->flattenKids($tree, $kid);
            }
        }

    }

//    public  function prepareCollectionRaw($coll, $columns)
//    {
//
//        $data = [];
//        $coll = $coll->get();
//        foreach ($coll as $obj)
//        {
//            $row = [];
//
//            foreach ($columns as $col)
//            {
//                $row[$col['data']] = '';
//                $row[$col['data']] = strip_tags(preg_replace('#\<sup.+</sup>#Us', '', $obj->getDescribedColumnValue($col['data'])));
//
//            }
//            $data[] = $row;
//        }
//
//        return $data;
//    }//



    function sortArray( $data, $field )
    {
        $field = (array) $field;
        uasort( $data, function($a, $b) use($field) {
            $retval = 0;
            foreach( $field as $fieldname ) {
                if( $retval == 0 ) $retval = strnatcmp( $a[$fieldname], $b[$fieldname] );
            }
            return $retval;
        } );
        return $data;
    }

    function sortArrayObjects( $data, $field )
    {
        $field = (array) $field;
        uasort( $data, function($a, $b) use($field) {
            $retval = 0;
            foreach( $field as $fieldname ) {
                if( $retval == 0 ) $retval = strnatcmp( $a->$fieldname, $b->$fieldname);
            }
            return $retval;
        } );
        return $data;
    }

    function resolveModelView(CrudModel $model, $view)
    {
        $hints = $this->app['view']->getFinder()->getHints();
        $key = "crud." . $model->classViewName . "." . $model->getScope();
        $source = isset($hints[$key]) ? $hints[$key] : [];
        if (empty($source))
        {
            $target = [];
            $add = [
                '/crud',
                '/crud/models',
                '/crud/models/' . $model->classViewName,
                '/crud/models/' . $model->classViewName . '/' . $model->getScope(),
            ];
            foreach ($this->app['config']['view.paths'] as $path)
            {
                if (isset($hints['crud']))
                {
                    foreach ($hints['crud'] as $entry)
                    {
                        if (!in_array($entry, $target))
                        {
                            $target[] = $entry;
                        }
                    }
                }
                if (!in_array($path, $target))
                {
                    $target[] = $path;
                }
                foreach ($add as $entry)
                {
                    $tpath = $path . $entry;
                    if (!in_array($tpath, $source))
                    {
                        array_unshift($target, $tpath);
                    }
                }
            }
            if (!empty($target))
            {
                $this->app['view']->getFinder()->prependNamespace($key, $target);
            }
        }
        return $key . "::" . $view;
    }

    function getModelClass($model)
    {
        return $this->app['config']['crud_common.model_namespace'] . '\\' . studly_case($model);
    }

    function getModelInstance($model, $scope = CrudModel :: DEFAULT_SCOPE, $id = null)
    {
        $class = $this->getModelClass($model);
        if (!empty($id))
        {
            $obj = $class::firstOrNew(['id'=>(int)$id]);
        }
        else
        {
            $obj = $this->app->make($class);
        }
        $obj->setScope($scope);
        return $obj;
    }

}
