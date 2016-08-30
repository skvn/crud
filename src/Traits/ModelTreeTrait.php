<?php

namespace Skvn\Crud\Traits;

/*
with(new Page())->makeRoot();
with(new Page())->makePreviousSiblingOf(Page::find(1))
with(new Page())->makeNextSiblingOf(Page::find(1))
with(new Page())->makeLastChildOf(Page::find(5))
with(new Page())->makeFirstChildOf(Page::find(2))
Page::find(2)->children()
Page::find(2)->parent()
Page::find(2)->sibling()
Page::find(2)->isDescendant(Page::find(3))
Page::find(2)->isAncestor(Page::find(3))
Page::find(2)->isLeaf()
Page::allRoot()
Page::allLeaf()
Page::find(2)->getTreeDepth()
*/


use Skvn\Crud\Exceptions\ConfigException;
use Skvn\Crud\Exceptions\TreeException;

trait ModelTreeTrait
{
    protected $tree_parent_ids = null;
    protected $tree_parents = null;

//    public function makeRoot()
//    {
//        $title_f = ($this->confParam('title_field')?$this->confParam('title_field'):'title');
//        $this->fill(array(
//            $title_f => 'Верхний уровень',
//            $this->getColumnTreePath() => $this->treePathSeparator(),
//            $this->getColumnTreePid() => 0,
//            $this->getColumnTreeDepth() => 0,
//            $this->getColumnTreeOrder() => (static::allRoot()->max($this->getColumnTreeOrder()) + 1)
//        ));
//
//        $this->save();
//
//        return $this;
//    }

    public function treePathSeparator()
    {
        return defined('static::TREE_PATH_SEPARATOR') ? static :: TREE_PATH_SEPARATOR : '.';
    }

//    public function makeFirstChildOf($parent)
//    {
//        if ($this->exists and $this->isAncestor($parent)) throw new \Exception('Cant move Ancestor to Descendant');
//        if (!$parent->exists) throw new \Exception('Parent doesnt exist');
//
//        $this_ = $this;
//
//        $this->app['db']->transaction(function() use (&$this_, &$parent)
//        {
//            $parent->children(1)->increment($this_->getColumnTreeOrder());
//
//            if ($this_->exists)
//            {
//                $children = $this_->children()->get();
//
//                foreach($children as $child)
//                {
//                    $child->update(array(
//                        $child->getColumnTreePath() => str_replace($this_->getTreePath(), $parent->getTreePath().$parent->getKey().'.', $child->getTreePath()),
//                        $child->getColumnTreeDepth() => ( $parent->getTreeDepth() + 1 + ($child->getTreeDepth() - $this_->getTreeDepth()) ),
//                    ));
//                }
//            }
//
//            $this_->fill(array(
//                $this_->getColumnTreePath() => $parent->getTreePath().$parent->getKey().'.',
//                $this_->getColumnTreePid() => $parent->getKey(),
//                $this_->getColumnTreeOrder() => 0,
//                $this_->getColumnTreeDepth() => ($parent->getTreeDepth() + 1)
//            ));
//
//            $this_->save();
//        });
//
//        return $this;
//    }


//    public function makeChildOf($parent, $position)
//    {
//        if ($this->exists and $this->isAncestor($parent)) throw new \Exception('Cant move Ancestor to Descendant');
//        if (!$parent->exists) throw new \Exception('Parent doesnt exist');
//
//        $this_ = $this;
//
//        $this->app['db']->transaction(function() use (&$this_, &$parent, $position)
//        {
//            $parent->children(1)->where($this_->getColumnTreeOrder(),'>',$position)->increment($this_->getColumnTreeOrder());
//
//            if ($this_->exists)
//            {
//                $children = $this_->children()->get();
//
//                foreach($children as $child)
//                {
//                    $child->update(array(
//                        $child->getColumnTreePath() => str_replace($this_->getTreePath(), $parent->getTreePath().$parent->getKey().'.', $child->getTreePath()),
//                        $child->getColumnTreeDepth() => ( $parent->getTreeDepth() + 1 + ($child->getTreeDepth() - $this_->getTreeDepth()) ),
//                    ));
//                }
//            }
//
//            $this_->fill(array(
//                $this_->getColumnTreePath() => $parent->getTreePath().$parent->getKey().'.',
//                $this_->getColumnTreePid() => $parent->getKey(),
//                $this_->getColumnTreeOrder() => $position,
//                $this_->getColumnTreeDepth() => ($parent->getTreeDepth() + 1)
//            ));
//
//            $this_->save();
//        });
//
//        return $this;
//    }

    public function makeLastChildOf($parent)
    {
        if (! $parent->exists) {
            throw new \Exception('Parent doesnt exist');
        }
        if ($this->exists && $this->isAncestor($parent)) {
            throw new TreeException('Cant move Ancestor to Descendant');
        }

        if ($this->exists) {
            $children = $this->children()->get();

            foreach ($children as $child) {
                $child->setAttribute($child->treePathColumn(), str_replace($this->getTreePath(), $parent->getTreePath().$parent->getKey().$this->treePathSeparator(), $child->getTreePath()));
                $child->setAttribute($child->treeDepthColumn(), $parent->getTreeDepth() + 1 + ($child->getTreeDepth() - $this->getTreeDepth()));
                $child->save();
            }
        }

        $this->forceFill([
            $this->treePathColumn()  => $parent->getTreePath().$parent->getKey().$this->treePathSeparator(),
            $this->treePidColumn()   => $parent->getKey(),
            $this->treeOrderColumn() => ($parent->children(1)->max($parent->treeOrderColumn()) + 1),
            $this->treeDepthColumn() => ($parent->getTreeDepth() + 1),
        ]);

        $this->save();

        return $this;
    }

    public function treeReorderRows($args = [])
    {
        if (! empty($args['reorder'])) {
            foreach ($args['reorder'] as $id => $priority) {
                \DB :: table($this->getTable())->where('id', $id)->update([$this->treeOrderColumn() => $priority]);
            }
            $row = \DB :: selectOne('select '.$this->getKeyName().', '.$this->treePidColumn().' from '.$this->getTable().' where '.$this->getKeyName().'=? order by '.$this->treeOrderColumn(), [$id]);
            $this->treeReorderLevel($row[$this->treePidColumn()]);
        }
    }

    public function treeReorderLevel($parent_id)
    {
        \DB :: statement('set @pri=0');
        \DB :: statement('update '.$this->getTable().' set '.$this->treeOrderColumn().' = (@pri:=@pri+1) where '.$this->treePidColumn().'=? order by '.$this->treeOrderColumn(), [$parent_id]);
    }

//    public function makePreviousSiblingOf($sibling)
//    {
//        return $this->processSiblingOf($sibling, '>=');
//    }
//
//    public function makeNextSiblingOf($sibling)
//    {
//        return $this->processSiblingOf($sibling, '>');
//    }

    public function parent()
    {
        return $this->belongsTo(get_class($this), $this->treePidColumn());
    }

    public function siblings()
    {
        return $this->newQuery()->where($this->treePidColumn(), '=', $this->getTreePid());
    }

    public function children($depth = 1)
    {
        if ($depth == 1) {
            return $this->hasMany(get_class($this), $this->treePidColumn());
        } else {
            $query = $this->newQuery();
            $query->where($this->treePathColumn(), 'like', $this->getTreePath().$this->getKey().$this->treePathSeparator().'%');

            if ($depth) {
                $query->where($this->treeDepthColumn(), '<=', $this->getTreeDepth() + $depth);
            }

            return $query;
        }
    }

    public function isDescendant($ancestor)
    {
        if (! $this->exists) {
            throw new \Exception('Model doesnt exist');
        }

        return strpos($this->getTreePath(), $ancestor->getTreePath().$ancestor->getKey().$this->treePathSeparator()) !== false && $ancestor->getTreePath() !== $this->getTreePath();
    }

    public function isAncestor($descendant)
    {
        if (! $this->exists) {
            throw new \Exception('Model doesnt exist');
        }

        return strpos($descendant->getTreePath(), $this->getTreePath().$this->getKey().$this->treePathSeparator()) !== false && $descendant->getTreePath() !== $this->getTreePath();
    }

    public function isLeaf()
    {
        if (! $this->exists) {
            throw new \Exception('Model doesnt exist');
        }

        return ! count($this->children(1)->get()->toArray());
    }

//    public function relativeDepth($object)
//    {
//        return abs($this->getTreeDepth() - $object->getTreeDepth());
//    }

//    public static function allRoot()
//    {
//        $instance = new static;
//
//        $query = $instance->newQuery()->where($instance->getColumnTreePid(), '=', 0);
//
//        return $query;
//    }

//    public static function allLeaf()
//    {
//        $instance = with(new static);
//
//        $query = $instance->newQuery();
//
//        $query->select($instance->getTable().'.*');
//
//        $query->leftJoin($instance->getTable().' as t_2', function($join) use ($instance)
//        {
//            $join->on($instance->getTable().'.'.$instance->getKeyName(), '=', 't_2.'.$instance->getColumnTreePid());
//        })
//            ->whereNull('t_2.id');
//
//        return $query;
//    }

    public function getTreePid()
    {
        return $this->getAttribute($this->treePidColumn());
    }

    public function getTreeOrder()
    {
        return $this->getAttribute($this->treeOrderColumn());
    }

    public function getTreePath()
    {
        return $this->getAttribute($this->treePathColumn());
    }

    public function getTreeDepth()
    {
        return $this->getAttribute($this->treeDepthColumn());
    }

    public function treePidColumn()
    {
        return $this->getTreeConfig('pid_column');
    }

    public function treeOrderColumn()
    {
        return $this->getTreeConfig('order_column');
    }

    public function treePathColumn()
    {
        return $this->getTreeConfig('path_column');
    }

    public function treeDepthColumn()
    {
        return $this->getTreeConfig('depth_column');
    }

//    protected function processSiblingOf($sibling, $op)
//    {
//        if ($this->exists and $this->isAncestor($sibling)) throw new \Exception('Cant move Ancestor to Descendant');
//        if (!$sibling->exists) throw new \Exception('Sibling doesnt exist');
//
//        $this_ = &$this;
//
//        $this->app['db']->transaction(function() use (&$this_, &$sibling, $op)
//        {
//            $sibling->sibling()->where($this_->getColumnTreeOrder(), $op, $sibling->getTreeOrder())->increment($this_->getColumnTreeOrder());
//
//            if ($this_->exists)
//            {
//                $children = $this_->children()->get();
//
//                foreach($children as $child)
//                {
//                    $child->update(array(
//                        $child->getColumnTreePath() => str_replace($this_->getTreePath(), $sibling->getTreePath(), $child->getTreePath()),
//                        $child->getColumnTreeDepth() => ( $sibling->getTreeDepth() + ($child->getTreeDepth() - $this_->getTreeDepth()) ),
//                    ));
//                }
//            }
//
//            $this_->fill(array(
//                $this_->getColumnTreePath() => $sibling->getTreePath(),
//                $this_->getColumnTreePid() => $sibling->getTreePid(),
//                $this_->getColumnTreeOrder() => $sibling->getTreeOrder()+($op=='>'?1:0),
//                $this_->getColumnTreeDepth() => $sibling->getTreeDepth(),
//            ));
//
//            $this_->save();
//        });
//
//        return $this;
//
//    }//


//    public  function getAllTree($where=null)
//    {
//        //check if root exists
//        $root = self::where($this->getColumnTreePid(),0)->first();
//        if (!$root)
//        {
//            $this->makeRoot();
//        }
//        if (!$where) {
//
//            $coll = self::all();
//        } else {
//
//            $coll = self::query();
//            foreach ($where as $values)
//            {
//                $this->applyFilterWhere($coll, $values);
//            }
//            $coll = $coll->get();
//        }
//        $tree = [];
//        $tree = $this->processKids($tree,$coll);
//
//        return $tree;
//
//
//    }

//    private function processKids(array &$tree, $collection, $parent_id = 0)
//    {
//
//        $branch = array();
//        foreach ($collection as $element)
//        {
//            if ($element->getTreePid() == $parent_id)
//            {
//                $children = $this->processKids($tree, $collection, $element->id);
//                $children = $this->app['skvn.crud']->sortArrayObjects($children,'tree_order');
//                if ($children)
//                {
//                    $element->kids = $children;
//                }
//                $branch[$element->id] = $element;
//
//            }
//        }
//        return $branch;
//    }

    public function saveTree($input)
    {
        if ($this->id == $this->rootId()) {
            return $this->save();
        }
        if (empty($input[$this->treePidColumn()])) {
            if (! $this->exists) {
                throw new TreeException('Unable to create tree node. Parent is not set');
            }

            return $this->save();
        }



        $parent_id = $input[$this->treePidColumn()];

//        if (empty($parent))
//        {
//            $parent = $this->rootId();
//        }

        if (! $this->exists) {
            $this->moveTreeAction($parent_id, 'last_child');
        } else {
            $oldParent = $this->getAttribute($this->treePidColumn());
            if ($oldParent == $parent_id) {
                $this->save();
            } else {
                $this->moveTreeAction($parent_id, 'last_child');
            }
        }
    }

//

    public function moveTreeAction($parent, $position, $ref_id = null)
    {
        $parentObj = self::find($parent);
        switch ($position) {
            case 'last_child':
                $this->makeLastChildOf($parentObj);
                break;
            default:
                throw new TreeException('Unknown position for node moving: '.$position);
                break;
        }


//        if ($position == 0)
//        {
//            $this->makeFirstChildOf($parentObj);
//        }  else if ($position == -1)
//        {
//            $this->makeLastChildOf($parentObj);
//        }
//        else {
//            $this->makeChildOf($parentObj, $position);
//        }
//
//        return true;
    }

    public function getParents()
    {
        if (is_null($this->parent_ids)) {
            $this->parent_ids = [];

            $p_ids = explode($this->treePathSeparator(), $this->getTreePath());
            foreach ($p_ids as $id) {
                if (intval($id) > 0 && $id != $this->getRootId()) {
                    $this->parent_ids[] = $id;
                }
            }
            if (count($this->parent_ids)) {
                $this->parents = static::find($this->parents_ids);
            }
        }

        return $this->parents;
    }

    public function rootId()
    {
        if (defined('static::ROOT_ID')) {
            return static :: ROOT_ID;
        }
        throw new ConfigException('Root node for '.$this->classViewName.' not defined');
    }
}
