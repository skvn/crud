<?php namespace LaravelCrud\Helper;

//use LaravelCrud\CrudConfig;
use LaravelCrud\Model\CrudModel;

class CrudHelper {

    protected $app;


    function __construct(\Illuminate\Foundation\Application $app)
    {
        $this->app = $app;
    }

    public function prepareCollectionForView ($coll, $args, $viewType)
    {
        switch ($viewType) {

            case 'data_tables':
                return $this->prepareCollectionForDT($coll, $args);
                break;

            case 'tree':
                return $this->prepareCollectionForTree($coll, $args);
                break;

            case 'tree_flattened':
                return $this->prepareCollectionForTreeFlat($coll, $args);
                break;
            default:
                return $coll->get();
                break;
        }
    }

    public  function prepareCollectionForTree($coll, $args)
    {
        //$coll->orderBy('full_path', 'asc');
        //$coll->orderBy('tree_path', 'asc');

        //return $coll->get();
        return $coll;
    }

    public   function prepareCollectionForTreeFlat($coll, $args)
    {
        $ret = [];
        foreach ($coll as $root)
        {
           $this->flattenKids($ret,$root);
        }

        return $ret;

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

    public  function prepareCollectionForDT($coll, $args)
    {


        $columns = $args['columns'];
        //var_dump($columns);
        $order = $args['order'];
        if (!empty($order))
        {
            if (is_array($order)) {
                foreach ($order as $oc) {
                    $coll->orderBy(!empty($columns[$oc['column']]['name']) ? $columns[$oc['column']]['name'] : $columns[$oc['column']]['data'], $oc['dir']);
                }
            }
        }

        $data = [];


        if ($coll->cnt) {
            $total = $coll->cnt;
        } else
        {
            $total = 0;
        }
        $q = $coll->getQuery();
        \Session :: set("current_query_info", ['sql' => $q->toSql(), 'bind' => $q->getBindings()]);
        $coll = $coll->get();

        foreach ($coll as $obj)
        {
            $row = [];

            foreach ($columns as $col)
            {

                $row[$col['data']] = '';
                $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);


            }
            $data[] = $row;
        }

        return [
            "recordsTotal"=>$total ,
            "recordsFiltered"=>$total,
            'data'=>$data
        ];
    }//


    public  function prepareCollectionRaw($coll, $columns)
    {

        $data = [];
        $coll = $coll->get();
        foreach ($coll as $obj)
        {
            $row = [];

            foreach ($columns as $col)
            {

                $row[$col['data']] = '';
                $row[$col['data']] = strip_tags($obj->getDescribedColumnValue($col['data']));

            }
            $data[] = $row;
        }

        return $data;
    }//



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
        $key = "crud." . $model->classViewName . "." . $model->config->getScope();
        $source = isset($hints[$key]) ? $hints[$key] : [];
        $target = [];
        $add = [
            '/crud',
            '/crud/models',
            '/crud/models/' . $model->classViewName,
            '/crud/models/' . $model->classViewName . '/' . $model->config->getScope(),
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
        return $key . "::" . $view;
    }

//    function resolveModelTemplate($model, $action, $scope = CrudConfig :: DEFAULT_SCOPE)
//    {
//
//        //var_dump(get_class($this->app));
//        $crud_views_path = \Config::get('view.model_views');
//        $views_path = \Config::get('view.paths');
//
//        $view_name = $crud_views_path.'/'.$model.'/'.str_replace('.','/',$scope . "_" . $action);
//
//        if (file_exists($view_name.'.twig'))
//        {
//            foreach ($views_path as $p)
//            {
//
//                if (strpos($view_name, $p) === 0)
//                {
//                    return str_replace($p.'/','',$view_name);
//                }
//            }
//        }
//
//
//        $view_name = $crud_views_path.'/'.$model.'/'.str_replace('.','/',$action);
//
//        if (file_exists($view_name.'.twig'))
//        {
//            foreach ($views_path as $p)
//            {
//
//                if (strpos($view_name, $p) === 0)
//                {
//                    return str_replace($p.'/','',$view_name);
//                }
//            }
//        }
//
//        return 'crud::'.$action;
//    }
} 