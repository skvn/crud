<?php namespace Skvn\Crud\Models;

use Illuminate\Container\Container;


class CrudModelCollectionBuilder
{
    protected $model;
    protected $app;
    protected $view_type;
    protected $columns;
    protected $collection;
    protected $params = [];

    function __construct(CrudModel $model)
    {
        $this->app = Container :: getInstance();
        $this->model = $model;
        $this->columns = $this->model->getListConfig('columns');
        $this->params['buttons'] = $this->model->getListConfig('buttons');
    }

    static function create(CrudModel $model, $view_type = null)
    {
        $obj = new self($model);
        if (!empty($view_type))
        {
            $obj->setViewType($view_type);
        }
        return $obj;
    }

    static function createDataTables(CrudModel $model)
    {
        return self :: create($model, "data_tables");
    }

    static function createTree(CrudModel $model)
    {
        return self :: create($model, "tree");
    }

    function setViewType($view_type)
    {
        $this->view_type = $view_type;
        return $this;
    }

    function setParams($params)
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    function createCollection($order = null)
    {
        $scope = $this->model->getScope();
        if (!empty($scope))
        {
            $method = camel_case('get_' . $scope . '_list_collection');
            $method_query = camel_case('get_' . $scope . '_list_query');
        }
        $joins =[];
        foreach ($this->columns as $listCol)
        {
            if ($relSpl = $this->model->resolveListRelation($listCol['data']))
            {
                $joins[$relSpl[0]] = function ($query) {};
            }
        }
        if (method_exists($this->model, $method))
        {
            $this->collection = $this->model->$method($order, $joins);
        }
        else if (method_exists($this->model, $method_query))
        {
            $this->collection = $this->model->$method_query($order, $joins);
        }
        else
        {
            $this->collection = $this->createBasicListQuery($joins);
        }
        return $this;
    }

    function getCollection()
    {
        return $this->collection;
    }

    function createBasicListQuery($joins)
    {
        $sort = $this->model->getListConfig('sort');
        $basic = $this->model->newQuery();

        if (count($joins))
        {
            $basic = $basic->with($joins);
        }

        if ($this->model->isTree())
        {
            $basic->orderBy($this->model->getColumnTreePath() , 'asc');
            $basic->orderBy($this->model->getColumnTreeOrder(), 'asc');
        }
        else
        {
            if (!empty($sort))
            {
                foreach ($sort as $o => $v)
                {
                    $basic->orderBy($o, $v);
                }
            }
        }

        return $basic;
    }

    function applyQueryFilter()
    {
        if ($this->model->isTree())
        {
            return $this;
        }
        $this->model->initFilter();
        $methodCond = camel_case('append_' . $this->model->getScope() . '_conditions');
        $conditions = $this->model->getFilter()->getConditions();
        if (method_exists($this->model, $methodCond))
        {
            $conditions= $this->model->$methodCond($conditions);
        }
        else
        {
            $conditions = $this->model->appendConditions($conditions);
        }
        if (is_array($conditions))
        {
            $this->applyConditions($conditions);
            //$this->collection->cnt = $this->collection->count();
        }
        return $this;
    }

    function applyConditions($conditions)
    {
        $conditions = $this->model->preApplyConditions($this->collection, $conditions);

        foreach ($conditions as $cond)
        {
            if (empty($cond['join']))
            {
                if (!empty($cond['cond']))
                {
                    $this->applyFilterWhere($cond['cond']);
                }
            }
            else
            {
                //use joins
                $this->collection->whereHas($cond['join'], function($query) use ($cond) {
                    $this->applyFilterWhere($cond['cond']);
                });
            }
        }

        return $this;
    }//

    function applyFilterWhere($cond)
    {
        if (is_string($cond))
        {
            $this->collection->whereRaw($cond);
        }
        else if (is_array($cond[0]))
        {
            //OR in AND
            $or_where = function ($query) use ($cond) {
                foreach ($cond as $i=>$one_cond)
                {
                    list($col, $act, $val) = $one_cond;
                    if ($i ==0)
                    {
                        $this->applyFilterWhere($one_cond);
                    }
                    else
                    {
                        $this->applyFilterOrWhere($one_cond);
                    }
                }
            };
            $this->collection->where($or_where);
        }
        else
        {
            //simple and
            list($col, $act, $val) = $cond;
            switch (strtolower($act))
            {
                case 'in':
                    $this->collection->whereIn($col, $val);
                    break;

                case 'between':
                    $this->collection->whereBetween($col, $val);
                    break;

                default:
                    $this->collection->where($col, $act, $val);
                    break;
            }
        }
        return $this;
    }//

    function applyFilterOrWhere($cond)
    {
        list($col, $act, $val) = $cond;
        switch (strtolower($act))
        {
            case 'in':
                $this->collection->orWhereIn($col, $val);
                break;

            case 'between':
                $this->collection->orWhereBetween($col, $val);
                break;

            default:
                $this->collection->orWhere($col, $act, $val);
                break;
        }
        return $this;
    }

    function paginate($skip, $take)
    {
        $this->collection->cnt = $this->collection->count();
        if ($take>0)
        {
            $this->collection->skip($skip)->take($take);
        }

        return $this;
    }

    function fetch()
    {
        switch ($this->view_type)
        {
            case 'data_tables':
                return $this->fetchDataTables();
            break;
            case 'tree':
                return $this->fetchTree();
            break;
            default:
                return $this->collection->get();
            break;
        }
    }

    function fetchDataTables()
    {
        $columns = $this->params['columns'];

        if (!empty($this->params['order']))
        {
            $order = $this->params['order'];
            if (is_array($order))
            {
                foreach ($order as $oc)
                {
                    $this->collection->orderBy(!empty($columns[$oc['column']]['name']) ? $columns[$oc['column']]['name'] : $columns[$oc['column']]['data'], $oc['dir']);
                }
            }
        }
        $data = [];
        $total = !empty($this->collection->cnt) ? $this->collection->cnt : 0;
        $q = $this->collection->getQuery();
        $this->app['session']->set("current_query_info", ['sql' => $q->toSql(), 'bind' => $q->getBindings()]);
        $rs = $this->collection->get();

        foreach ($rs as $obj)
        {
            $row = [];
            foreach ($columns as $col)
            {
                $row[$col['data']] = '';
                $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);
            }
            foreach ($this->columns as $col)
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

    }

    function fetchTree()
    {
        $data = $this->collection->get();
        $ret = [];
        foreach ($data as $row)
        {
            $text = $row->getTitle();
            if (!empty($this->columns))
            {
                foreach ($this->columns as $col)
                {
                    $text .= " <span class=\"badge\">".$row->getDescribedColumnValue($col['data'])."</span>";
                }
            }
            if (!empty($this->params['buttons']['single_edit']))
            {
                $text .= "&nbsp;&nbsp;<a class=\"text-info\" data-id=\"".$row->id."\" data-click=\"crud_event\" data-event=\"crud.edit_tree_element\"><i class=\"fa fa-edit\"> </i></a>";
            }
            if (!empty($this->params['buttons']['single_delete']))
            {
                $text .= "&nbsp;&nbsp;<a class=\"text-danger\" data-confirm=\"".trans('crud::messages.really_delete')."?\" data-id=\"".$row->id."\" data-click=\"crud_event\" data-event=\"crud.delete_tree_element\" ><i class=\"fa fa-trash-o\"> </i></a>";
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





}