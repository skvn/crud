<?php namespace Skvn\Crud\Helper;

use Skvn\Crud\CrudConfig;
use Skvn\Crud\Model\CrudModel;

class CrudHelper {

    protected $app;


    function __construct(\Illuminate\Foundation\Application $app)
    {
        $this->app = $app;
    }

    public function prepareCollectionForView ($coll, $args, $viewType, $config_cols=null)
    {
        switch ($viewType) {

            case 'data_tables':
                return $this->prepareCollectionForDT($coll, $args, $config_cols);
                break;

            case 'tree':
                return $this->prepareCollectionForTree($coll, $args, $config_cols);
                break;

            case 'tree_flattened':

                return $this->prepareCollectionForTreeFlat($coll, $args, $config_cols);
                break;
            default:
                return $coll->get();
                break;
        }
    }

    public  function prepareCollectionForTree($coll, $args, $columns)
    {

        $data = $coll->get();
        $ret = [];
        foreach ($data as $row)
        {

            $text = $row->getTitle();


            if (!empty($columns))
            {
                foreach ($columns as $col)
                {

                    $text .= " <span class=\"badge\">".$row->getDescribedColumnValue($col['data'])."</span>";

                }
            }

            $node = [
                'id'=>$row->id,
                'text'=>$text,
                'parent'=>($row->getAttribute($row->getColumnTreePid())==0?'#':$row->getAttribute($row->getColumnTreePid())),


            ];

            $ret[] = $node;
        }

        return $ret;
    }

    public   function prepareCollectionForTreeFlat($coll, $args)
    {

        return $coll->get();
//        $ret = [];
//        foreach ($coll as $root)
//        {
//           $this->flattenKids($ret,$root);
//        }
//
//        return $ret;

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

    public  function prepareCollectionForDT($coll, $args, $config_cols)
    {


        $columns = $args['columns'];

        if (!empty($args['order']))
        {
            $order = $args['order'];
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
        $this->app['session']->set("current_query_info", ['sql' => $q->toSql(), 'bind' => $q->getBindings()]);
        $coll = $coll->get();

        foreach ($coll as $obj)
        {
            $row = [];

            foreach ($columns as $col)
            {

                $row[$col['data']] = '';
                $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);


            }
            foreach ($config_cols as $col)
            {
                if (!empty($col['invisible']))
                {
                    $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);
                }
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
                $row[$col['data']] = strip_tags(preg_replace('#\<sup.+</sup>#Us', '', $obj->getDescribedColumnValue($col['data'])));

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
        if (empty($source))
        {
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
        }
        return $key . "::" . $view;
    }

    function getModelClass($model)
    {
        return $this->app['config']['crud_common.model_namespace'] . '\\' . studly_case($model);
    }

    function getModelInstance($model, $scope = CrudConfig :: DEFAULT_SCOPE, $id = null)
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
        $obj->config->setScope($scope);
        return $obj;
    }

}
