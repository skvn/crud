<?php namespace Skvn\Crud\Traits;

use Skvn\Crud\Exceptions\Exception as CrudException;
use Skvn\Crud\Models\CrudModelCollectionBuilder;

trait ModelListTrait
{

    function getListData($scope=null, $viewType='data_tables')
    {
        $skip = (int) $this->app['request']->get('start',0);
        $take =  (int) $this->app['request']->get('length',0);
        $order = $this->app['request']->get('order');

        return CrudModelCollectionBuilder :: create($this, $viewType)
                        ->createCollection($order)
                        ->applyQueryFilter()
                        ->paginate($skip, $take)
                        ->setParams($this->app['request']->all())
                        ->fetch();




//        if (!empty($scope))
//        {
//            $this->setScope($scope);
//        }
//        $config_cols = $this->getListConfig('columns');
//        $coll = $this->getListQuery($scope, $order);
//        if (!$this->isTree())
//        {
//            $coll = $this->applyQueryFilter($coll, $scope);
//            $coll = $this->paginateQuery($coll, $skip, $take);
//        }
//        //var_dump($coll->getQuery()->toSQL());
//        //var_dump($coll->getQuery()->getBindings());
//        $args = $this->app['request']->all();
//        $args['buttons'] = $this->getListConfig('buttons');

//        return $this->prepareCollectionForView($coll, $args, $viewType, $config_cols);
    }


    /**
     * DEPRECATED  use getListQuery instead
     * @param null $scope
     * @param null $order
     * @return mixed
     */
//    function getListCollection($scope=null, $order=null)
//    {
//        return $this->getListQuery($scope, $order);
//    }

    function getBasicListQuery($joins)
    {
        return CrudModelCollectionBuilder :: create($this)->createBasicListQuery($joins);
    }


    function applyFilterWhere($coll, $cond)
    {
        return CrudModelCollectionBuilder :: create($this)->applyFilterWhere($coll, $cond);
    }



//    function getListQuery($scope=null, $order=null)
//    {
//        if (!empty($scope))
//        {
//            $method = camel_case('get_' . $scope . '_list_collection');
//            $method_query = camel_case('get_' . $scope . '_list_query');
//        }
//        //define if need eager join
//        $listCols = $this->getListConfig('columns');
//        $joins =[];
//        foreach ($listCols as $listCol)
//        {
//            if ($relSpl = $this->resolveListRelation($listCol['data']))
//            {
//                $joins[$relSpl[0]] = function ($query) {
//                };
//            }
//        }
//
//        if (!empty($scope) && method_exists($this, $method))
//        {
//            return $this->$method($order, $joins);
//        }
//        else if (!empty($scope) && method_exists($this, $method_query))
//        {
//            return $this->$method_query($order, $joins);
//        }
//        else
//        {
//            return $this->getBasicListQuery($joins);
//        }
//    }//


//    function getBasicListQuery($joins)
//    {
//        $sort = $this->getListConfig('sort');
//        $basic = self::query();
//
//        if (count($joins))
//        {
//            $basic = $basic->with($joins);
//        }
//
//        if ($this->isTree())
//        {
//            $basic->orderBy($this->getColumnTreePath() , 'asc');
//            $basic->orderBy($this->getColumnTreeOrder(), 'asc');
//        }
//        else
//        {
//            if (!empty($sort))
//            {
//                foreach ($sort as $o => $v)
//                {
//                    $basic->orderBy($o, $v);
//                }
//            }
//        }
//
//        return $basic;
//    }

//    protected  function paginateQuery($coll, $skip, $take)
//    {
//        $coll->cnt = $coll->count();
//        if ($take>0)
//        {
//            $coll = $coll->skip($skip)->take($take);
//        }
//
//        return $coll;
//    }

//    public  function applyQueryFilter($coll, $scope)
//    {
//        $this->initFilter($scope);
//        if (!empty($scope))
//        {
//            $methodCond = camel_case('append_' . $scope . '_conditions');
//        }
//        $conditions = $this->filterObj->getConditions();
//        if (method_exists($this,$methodCond))
//        {
//            $conditions= $this->$methodCond($conditions);
//        }
//        else
//        {
//            $conditions = $this->appendConditions($conditions);
//        }
//
//        if (is_array($conditions))
//        {
//            $coll = $this->applyConditions($coll, $conditions);
//            $coll->cnt = $coll->count();
//        }
//
//
//        return $coll;
//
//    }//


//    public function applyConditions($coll, $conditions)
//    {
//        $conditions = $this->preApplyConditions($coll,$conditions);
//
//        foreach ($conditions as $cond)
//        {
//            if (empty($cond['join']))
//            {
//                if (!empty($cond['cond']))
//                {
//                    $coll = $this->applyFilterWhere($coll, $cond['cond']);
//                }
//            }
//            else
//            {
//                //use joins
//                $coll-> whereHas($cond['join'], function($query) use ($cond) {
//                    $query = $this->applyFilterWhere($query, $cond['cond']);
//                });
//            }
//        }
//
//        return $coll;
//    }//


//    protected function applyFilterWhere($coll, $cond)
//    {
//        if (is_string($cond))
//        {
//            $coll->whereRaw($cond);
//        }
//        else if (is_array($cond[0]))
//        {
//            //OR in AND
//            $or_where = function ($query) use ($cond) {
//
//                foreach ($cond as $i=>$one_cond)
//                {
//                    list($col, $act, $val) = $one_cond;
//                    if ($i ==0)
//                    {
//                        $query = $this->applyFilterWhere($query,$one_cond);
//                    }
//                    else
//                    {
//                        $query = $this->applyFilterOrWhere($query,$one_cond);
//                    }
//                }
//            };
//            $coll->where($or_where);
//        }
//        else
//        {
//            //simple and
//            list($col, $act, $val) = $cond;
//            switch (strtolower($act))
//            {
//                case 'in':
//                    $coll->whereIn($col, $val);
//                    break;
//
//                case 'between':
//                    $coll->whereBetween($col, $val);
//                    break;
//
//                default:
//                    $coll->where($col, $act, $val);
//                    break;
//            }
//        }
//
//        return $coll;
//    }//


//    protected function applyFilterOrWhere($coll, $cond)
//    {
//        list($col, $act, $val) = $cond;
//        switch (strtolower($act))
//        {
//            case 'in':
//                $coll->orWhereIn($col, $val);
//                break;
//
//            case 'between':
//                $coll->orWhereBetween($col, $val);
//                break;
//
//            default:
//                $coll->orWhere($col, $act, $val);
//                break;
//        }
//
//        return $coll;
//    }






//    public function prepareCollectionForView ($coll, $args, $viewType, $config_cols=null)
//    {
//        switch ($viewType) {
//
//            case 'data_tables':
//                return $this->prepareCollectionForDT($coll, $args, $config_cols);
//                break;
//
//            case 'tree':
//                return $this->prepareCollectionForTree($coll, $args, $config_cols);
//                break;
//
//            case 'tree_flattened':
//
//                return $this->prepareCollectionForTreeFlat($coll, $args, $config_cols);
//                break;
//            default:
//                return $coll->get();
//                break;
//        }
//    }

//    public  function prepareCollectionForDT($coll, $args, $config_cols)
//    {
//        $columns = $args['columns'];
//
//        if (!empty($args['order']))
//        {
//            $order = $args['order'];
//            if (is_array($order))
//            {
//                foreach ($order as $oc)
//                {
//                    $coll->orderBy(!empty($columns[$oc['column']]['name']) ? $columns[$oc['column']]['name'] : $columns[$oc['column']]['data'], $oc['dir']);
//                }
//            }
//        }
//
//        $data = [];
//
//        if ($coll->cnt)
//        {
//            $total = $coll->cnt;
//        }
//        else
//        {
//            $total = 0;
//        }
//        $q = $coll->getQuery();
//        $this->app['session']->set("current_query_info", ['sql' => $q->toSql(), 'bind' => $q->getBindings()]);
//        $coll = $coll->get();
//
//        foreach ($coll as $obj)
//        {
//            $row = [];
//            foreach ($columns as $col)
//            {
//                $row[$col['data']] = '';
//                $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);
//            }
//            foreach ($config_cols as $col)
//            {
//                if (!empty($col['invisible']))
//                {
//                    $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);
//                }
//            }
//            $data[] = $row;
//        }
//
//        return [
//            "recordsTotal"=>$total ,
//            "recordsFiltered"=>$total,
//            'data'=>$data
//        ];
//    }//


//    public  function prepareCollectionForTree($coll, $args, $columns)
//    {
//        $data = $coll->get();
//        $ret = [];
//        foreach ($data as $row)
//        {
//            $text = $row->getTitle();
//            if (!empty($columns))
//            {
//                foreach ($columns as $col)
//                {
//                    $text .= " <span class=\"badge\">".$row->getDescribedColumnValue($col['data'])."</span>";
//                }
//            }
//            if (!empty($args['buttons']['single_edit']))
//            {
//                $text .= "&nbsp;&nbsp;<a class=\"text-info\" data-id=\"".$row->id."\" data-click=\"crud_event\" data-event=\"crud.edit_tree_element\"><i class=\"fa fa-edit\"> </i></a>";
//            }
//            if (!empty($args['buttons']['single_delete']))
//            {
//                $text .= "&nbsp;&nbsp;<a class=\"text-danger\" data-confirm=\"".trans('crud::messages.really_delete')."?\" data-id=\"".$row->id."\" data-click=\"crud_event\" data-event=\"crud.delete_tree_element\" ><i class=\"fa fa-trash-o\"> </i></a>";
//            }
//            $node = [
//                'id'=>$row->id,
//                'text'=>$text,
//                'parent'=>($row->getAttribute($row->getColumnTreePid())==0?'#':$row->getAttribute($row->getColumnTreePid())),
//            ];
//            $ret[] = $node;
//        }
//
//        return $ret;
//    }

//    public   function prepareCollectionForTreeFlat($coll, $args)
//    {
//        return $coll->get();
////        $ret = [];
////        foreach ($coll as $root)
////        {
////           $this->flattenKids($ret,$root);
////        }
////
////        return $ret;
//    }

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