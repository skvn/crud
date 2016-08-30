<?php

namespace Skvn\Crud\Models;

use Illuminate\Container\Container;
use Illuminate\Support\Str;

class CrudModelCollectionBuilder
{
    protected $model;
    protected $app;
    protected $columns;
    protected $collectionQuery;
    protected $params = [];

    public function __construct(CrudModel $model, $args = [])
    {
        $this->app = Container :: getInstance();
        $this->model = $model;
        $this->columns = $this->model->getList()->getColumns();
        $this->params = $args;
        $this->params['buttons'] = $this->model->getList()->getParam('buttons');
        $this->params['sort'] = $this->model->getList()->getParam('sort');
        if (! isset($this->params['view_type'])) {
            $this->params['view_type'] = '';
        }
    }

    public static function create(CrudModel $model, $args = [])
    {
        $obj = new self($model, $args);
        $obj->createCollection();

        return $obj;
    }

    public static function createDataTables(CrudModel $model, $args = [])
    {
        $listType = $model->getList()->getParam('list_type');
        if ($listType && $listType == 'dt_tree') {
            $args['view_type'] = 'data_tables_tree';
        } else {
            $args['view_type'] = 'data_tables';
        }

        return self :: create($model, $args);
    }

    public static function createTree(CrudModel $model, $args = [])
    {
        $args['view_type'] = 'tree';

        return self :: create($model, $args);
    }

    public static function createQuery(CrudModel $model, $args = [])
    {
        $args['raw'] = true;

        return self :: create($model, $args);
    }

    public static function createEmpty(CrudModel $model, $args = [])
    {
        $obj = new self($model, $args);

        return $obj;
    }

    public function setViewType($view_type)
    {
        $this->params['view_type'] = $view_type;

        return $this;
    }

    public function setSearch($search = '')
    {
        $this->params['search'] = $search;

        return $this;
    }

    public function setParams($params)
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    public function createCollection()
    {
        if (! empty($this->params['raw'])) {
            $this->collectionQuery = $this->model->newQuery();

            return $this;
        }
//        if (!empty($scope))
//        {
//            $method = camel_case('get_' . $scope . '_list_collection');
//            $method_query = camel_case('get_' . $scope . '_list_query');
//        }
        $joins = [];
        foreach ($this->columns as $listCol) {
            //if ($relSpl = $this->model->resolveListRelation($listCol['data']))
            if ($relSpl = $this->model->crudRelations->resolveReference($listCol['data'])) {
                $joins[$relSpl['rel']] = function ($query) {
                };
            }
        }
        $scope = $this->model->getScope();
        if (method_exists($this->model, 'scope'.Str :: studly($scope))) {
            $this->collectionQuery = $this->model->{Str :: camel($scope)}();
        }
//        if (method_exists($this->model, $method))
//        {
//            $this->collectionQuery = $this->model->$method($joins);
//        }
//        else if (method_exists($this->model, $method_query))
//        {
//            $this->collectionQuery = $this->model->$method_query($joins);
//        }
        else {
            $this->collectionQuery = $this->createBasicListQuery($joins);
        }

        return $this;
    }

    public function getCollectionQuery()
    {
        return $this->collectionQuery;
    }

    public function setCollectionQuery($coll)
    {
        $this->collectionQuery = $coll;

        return $this;
    }

    public function createBasicListQuery($joins = [])
    {
        $basic = $this->model->newQuery();

        if (count($joins)) {
            $basic = $basic->with($joins);
        }

        if ($this->model->isTree()) {
            $basic->orderBy($this->model->treePathColumn(), 'asc');
            $basic->orderBy($this->model->treeOrderColumn(), 'asc');
        } else {
            if (! empty($this->params['sort'])) {
                foreach ($this->params['sort'] as $o => $v) {
                    $basic->orderBy($o, $v);
                }
            }
        }

        return $basic;
    }

    public function applyContextFilter()
    {
        //        if ($this->model->isTree())
//        {
//            return $this;
//        }
        $methodCond = camel_case('append_'.$this->model->getScope().'_conditions');
        $conditions = $this->model->getList()->getFilter()->getConditions();
        if (method_exists($this->model, $methodCond)) {
            $conditions = $this->model->$methodCond($conditions);
        } else {
            $conditions = $this->model->appendConditions($conditions);
        }
        if (! empty($this->params['search'])) {
            $c = [];
            foreach ($this->columns as $column) {
                if (! empty($column['searchable'])) {
                    $c[] = [$column['data'], 'like', $this->params['search'].'%'];
                }
            }
            if (! empty($c)) {
                $conditions[] = ['cond' => $c];
            }
        }

        if ($this->model->isTree()) {
            if (isset($this->params[$this->model->treePidColumn()])) {
                $root = $this->params[$this->model->treePidColumn()];
                if (intval($root) <= 0) {
                    $root = $this->model->rootId();
                }
                $conditions[$this->model->treePidColumn()]['cond'] = [$this->model->treePidColumn(), '=', $root];
            }
        }

        //\Log :: info($conditions, ['browsify' => 1]);
        if (is_array($conditions)) {
            $this->applyConditions($conditions);
            //$this->collection->cnt = $this->collection->count();
        }


        return $this;
    }

    public function applyConditions($conditions)
    {
        $conditions = $this->model->preApplyConditions($this->collectionQuery, $conditions);

        foreach ($conditions as $cond) {
            if (empty($cond['join'])) {
                if (! empty($cond['cond'])) {
                    $this->applyFilterWhere($cond['cond']);
                }
            } else {
                //use joins
                $this->collectionQuery->whereHas($cond['join'], function ($query) use ($cond) {
                    $this->applyFilterWhere($cond['cond']);
                });
            }
        }


        return $this;
    }

//

    public function applyFilterWhere($cond, $q = null)
    {
        if (is_string($cond)) {
            $this->collectionQuery->whereRaw($cond);
        } elseif (is_array($cond[0])) {
            //OR in AND
            $or_where = function ($query) use ($cond) {
                foreach ($cond as $i => $one_cond) {
                    list($col, $act, $val) = $one_cond;
                    if ($i == 0) {
                        $this->applyFilterWhere($one_cond, $query);
                    } else {
                        $this->applyFilterOrWhere($one_cond, $query);
                    }
                }
            };
            $this->collectionQuery->where($or_where);
        } else {
            //simple and
            list($col, $act, $val) = $cond;
            $coll = is_null($q) ? $this->collectionQuery : $q;
            switch (strtolower($act)) {
                case 'in':
                    $coll->whereIn($col, $val);
                    break;

                case 'between':
                    $coll->whereBetween($col, $val);
                    break;

                default:
                    $coll->where($col, $act, $val);
                    break;
            }
        }

        return $this;
    }

//

    public function applyFilterOrWhere($cond, $q = null)
    {
        list($col, $act, $val) = $cond;
        $coll = is_null($q) ? $this->collectionQuery : $q;
        switch (strtolower($act)) {
            case 'in':
                $coll->orWhereIn($col, $val);
                break;

            case 'between':
                $coll->orWhereBetween($col, $val);
                break;

            default:
                $coll->orWhere($col, $act, $val);
                break;
        }

        return $this;
    }

    public function paginate($skip, $take)
    {
        //var_dump(get_class($this->collection));
        $this->collectionQuery->cnt = $this->collectionQuery->count();
        if ($take > 0) {
            $this->collectionQuery->skip($skip)->take($take);
        }

        return $this;
    }

    public function fetch()
    {
        switch ($this->params['view_type']) {
            case 'data_tables':
                return $this->fetchDataTables();
            break;

            case 'data_tables_tree':
                return $this->fetchDataTablesTree();
                break;

            case 'tree':
                return $this->fetchTree();
            break;
            default:
                return $this->collectionQuery->get();
            break;
        }
    }

    public function fetchDataTables()
    {
        $columns = $this->params['columns'] ?? $this->columns;

        if (! empty($this->params['order'])) {
            $this->collectionQuery->getQuery()->orders = [];
            $order = $this->params['order'];
            if (is_array($order)) {
                foreach ($order as $oc) {
                    $this->collectionQuery->orderBy(! empty($columns[$oc['column']]['name']) ? $columns[$oc['column']]['name'] : $columns[$oc['column']]['data'], $oc['dir']);
                }
            }
        }
        $data = [];
        $total = ! empty($this->collectionQuery->cnt) ? $this->collectionQuery->cnt : 0;
        $q = $this->collectionQuery->getQuery();
        $this->app['session']->set('current_query_info', ['sql' => $q->toSql(), 'bind' => $q->getBindings()]);
        \Log :: info($this->collectionQuery->getQuery()->toSQL(), ['browsify' => true]);
        \Log :: info($this->collectionQuery->getQuery()->getBindings(), ['browsify' => true]);
        $rs = $this->collectionQuery->get();

        foreach ($rs as $obj) {
            $row = [];
            foreach ($columns as $col) {
                $row[$col['data']] = '';
                $args = [];
                if (! empty($col['format'])) {
                    $args['formatter'] = $col['format'];
                }
                if (! empty($col['format_args'])) {
                    $args = array_merge($args, $col['format_args']);
                }
                $row[$col['data']] = $obj->formatted($col['data'], $args);
            }
//            foreach ($this->columns as $col)
//            {
//                if (!empty($col['invisible']))
//                {
//                    $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);
//                }
//                if (isset($row[$col['data']]) && !empty($col['format']))
//                {
//                    $row[$col['data']] = $obj->getDescribedColumnValue($col['data'], $col['format'], $col['format_args'] ?? []);
//                }
//            }
            $data[] = $row;
        }

        return [
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ];
    }

    public function fetchDataTablesTree()
    {
        $columns = $this->params['columns'] ?? $this->columns;



        $this->collectionQuery->withCount('children');

        if (! empty($this->params['order'])) {
            $order = $this->params['order'];
            if (is_array($order)) {
                foreach ($order as $oc) {
                    $this->collection->orderBy(! empty($columns[$oc['column']]['name']) ? $columns[$oc['column']]['name'] : $columns[$oc['column']]['data'], $oc['dir']);
                }
            }
        }
        $data = [];
        $total = ! empty($this->collectionQuery->cnt) ? $this->collectionQuery->cnt : 0;
        $q = $this->collectionQuery->getQuery();
        $this->app['session']->set('current_query_info', ['sql' => $q->toSql(), 'bind' => $q->getBindings()]);
        \Log :: info($this->collectionQuery->getQuery()->toSQL(), ['browsify' => true]);
        \Log :: info($this->collectionQuery->getQuery()->getBindings(), ['browsify' => true]);
        $rs = $this->collectionQuery->get();

        foreach ($rs as $obj) {
            $row = [];
            foreach ($columns as $col) {
                $row[$col['data']] = '';
                $args = [];
                if (! empty($col['format'])) {
                    $args['formatter'] = $col['format'];
                }
                if (! empty($col['format_args'])) {
                    $args = array_merge($args, $col['format_args']);
                }
                $row[$col['data']] = $obj->formatted($col['data'], $args);
            }
//            foreach ($this->columns as $col)
//            {
//                if (!empty($col['invisible']))
//                {
//                    $row[$col['data']] = $obj->getDescribedColumnValue($col['data']);
//                }
//                if (isset($row[$col['data']]) && !empty($col['format']))
//                {
//                    $row[$col['data']] = $obj->getDescribedColumnValue($col['data'], $col['format'], $col['format_args'] ?? []);
//                }
//            }
            $treeColumn = $obj->treePidColumn();
            $row[$treeColumn] = $obj->$treeColumn;
            if ($obj->children_count > 0) {
                $row['__has_children'] = true;
            } else {
                $row['__has_children'] = false;
            }
            $data[] = $row;
        }

        return [
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ];
    }

    public function fetchTree()
    {
        $data = $this->collectionQuery->get();
        $ret = [];
        foreach ($data as $row) {
            $text = $row->getTitle();
            if (! empty($this->columns)) {
                foreach ($this->columns as $col) {
                    $text .= ' <span class="badge">'.$row->formatted($col['data']).'</span>';
                }
            }
            if (! empty($this->params['buttons']['single_edit'])) {
                $text .= '&nbsp;&nbsp;<a class="text-info" data-id="'.$row->id.'" data-click="crud_event" data-event="crud.edit_tree_element"><i class="fa fa-edit"> </i></a>';
            }
            if (! empty($this->params['buttons']['single_delete'])) {
                $text .= '&nbsp;&nbsp;<a class="text-danger" data-confirm="'.trans('crud::messages.really_delete').'?" data-id="'.$row->id.'" data-click="crud_event" data-event="crud.delete_tree_element" ><i class="fa fa-trash-o"> </i></a>';
            }
            $node = [
                'id'     => $row->id,
                'text'   => $text,
                'parent' => ($row->getAttribute($row->treePidColumn()) == 0 ? '#' : $row->getAttribute($row->treePidColumn())),
            ];
            $ret[] = $node;
        }

        return $ret;
    }

    public function fetchRaw($columns)
    {
        $data = [];
        foreach ($this->collectionQuery->get() as $obj) {
            $row = [];

            foreach ($columns as $col) {
                if ($col['data'] == 'actions') {
                    continue;
                }
                $row[$col['data']] = '';
                $row[$col['data']] = strip_tags(preg_replace('#\<sup.+</sup>#Us', '', $obj->formatted($col['data'])));
            }
            $data[] = $row;
        }

        return $data;
    }

//

    public function count()
    {
        return $this->collectionQuery->count();
    }
}
