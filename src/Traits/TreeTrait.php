<?php namespace Skvn\Crud\Traits;


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


trait TreeTrait  {

    //protected $columnTreePid = 'tree_pid';
    //protected $columnTreeOrder = 'tree_order';
    //protected $columnTreePath = 'tree_path';
    //protected $columnTreeDepth = 'tree_depth';

    protected $parents;
    
    public function makeRoot()
    {
        $title_f = ($this->confParam('title_field')?$this->confParam('title_field'):'title');
        $this->fill(array(
            $title_f => 'Верхний уровень',
            $this->getColumnTreePath() => '.0.',
            $this->getColumnTreePid() => 0,
            $this->getColumnTreeDepth() => 0,
            $this->getColumnTreeOrder() => (static::allRoot()->max($this->getColumnTreeOrder()) + 1)
        ));

        $this->save();

        return $this;
    }

    public function makeFirstChildOf($parent)
    {
        if ($this->exists and $this->isAncestor($parent)) throw new \Exception('Cant move Ancestor to Descendant');
        if (!$parent->exists) throw new \Exception('Parent doesnt exist');

        $this_ = $this;

        $this->app['db']->transaction(function() use (&$this_, &$parent)
        {
            $parent->children(1)->increment($this_->getColumnTreeOrder());

            if ($this_->exists)
            {
                $children = $this_->children()->get();

                foreach($children as $child)
                {
                    $child->update(array(
                        $child->getColumnTreePath() => str_replace($this_->getTreePath(), $parent->getTreePath().$parent->getKey().'.', $child->getTreePath()),
                        $child->getColumnTreeDepth() => ( $parent->getTreeDepth() + 1 + ($child->getTreeDepth() - $this_->getTreeDepth()) ),
                    ));
                }
            }

            $this_->fill(array(
                $this_->getColumnTreePath() => $parent->getTreePath().$parent->getKey().'.',
                $this_->getColumnTreePid() => $parent->getKey(),
                $this_->getColumnTreeOrder() => 0,
                $this_->getColumnTreeDepth() => ($parent->getTreeDepth() + 1)
            ));

            $this_->save();
        });

        return $this;
    }


    public function makeChildOf($parent, $position)
    {
        if ($this->exists and $this->isAncestor($parent)) throw new \Exception('Cant move Ancestor to Descendant');
        if (!$parent->exists) throw new \Exception('Parent doesnt exist');

        $this_ = $this;

        $this->app['db']->transaction(function() use (&$this_, &$parent, $position)
        {
            $parent->children(1)->where($this_->getColumnTreeOrder(),'>',$position)->increment($this_->getColumnTreeOrder());

            if ($this_->exists)
            {
                $children = $this_->children()->get();

                foreach($children as $child)
                {
                    $child->update(array(
                        $child->getColumnTreePath() => str_replace($this_->getTreePath(), $parent->getTreePath().$parent->getKey().'.', $child->getTreePath()),
                        $child->getColumnTreeDepth() => ( $parent->getTreeDepth() + 1 + ($child->getTreeDepth() - $this_->getTreeDepth()) ),
                    ));
                }
            }

            $this_->fill(array(
                $this_->getColumnTreePath() => $parent->getTreePath().$parent->getKey().'.',
                $this_->getColumnTreePid() => $parent->getKey(),
                $this_->getColumnTreeOrder() => $position,
                $this_->getColumnTreeDepth() => ($parent->getTreeDepth() + 1)
            ));

            $this_->save();
        });

        return $this;
    }

    public function makeLastChildOf($parent)
    {
        if ($this->exists and $this->isAncestor($parent)) throw new \Exception('Cant move Ancestor to Descendant');
        if (!$parent->exists) throw new \Exception('Parent doesnt exist');

        $this_ = $this;

        $this->app['db']->transaction(function() use (&$this_, &$parent)
        {
            if ($this_->exists)
            {
                $children = $this_->children()->get();

                foreach($children as $child)
                {
                    if ($child) {

                        $child->update(array(
                            $child->getColumnTreePath() => str_replace($this_->getTreePath(), $parent->getTreePath() . $parent->getKey() . '.', $child->getTreePath()),
                            $child->getColumnTreeDepth() => ($parent->getTreeDepth() + 1 + ($child->getTreeDepth() - $this_->getTreeDepth())),
                        ));
                    }
                }
            }

            $this_->fill(array(
                $this_->getColumnTreePath() => $parent->getTreePath().$parent->getKey().'.',
                $this_->getColumnTreePid() => $parent->getKey(),
                $this_->getColumnTreeOrder() => ($parent->children(1)->max($parent->getColumnTreeOrder())+1),
                $this_->getColumnTreeDepth() => ($parent->getTreeDepth() + 1)
            ));

            $this_->save();
        });

        return $this;
    }

    public function makePreviousSiblingOf($sibling)
    {
        return $this->processSiblingOf($sibling, '>=');
    }

    public function makeNextSiblingOf($sibling)
    {
        return $this->processSiblingOf($sibling, '>');
    }

//    public function parent()
//    {
//        return $this->newQuery()->where($this->getKeyName(), '=', $this->getTreePid());
//    }

    public function parent()
    {
        return $this->belongsTo(get_class($this),$this->getColumnTreePid());
    }
    
    public function sibling()
    {
        return $this->newQuery()->where($this->getColumnTreePid(), '=', $this->getTreePid());
    }

    public function children($depth=1)
    {

        if ($depth == 1)
        {
            return $this->hasMany(get_class($this),$this->getColumnTreePid());

        } else {
            
            $query = $this->newQuery();
            $query->where($this->getColumnTreePath(), 'like', $this->getTreePath() . $this->getKey() . '.%');
            
            if ($depth) {
                $query->where($this->getColumnTreeDepth(), '<=', $this->getTreeDepth() + $depth);
            }

            return $query;
        }
    }

    public function isDescendant($ancestor)
    {
        if (!$this->exists) throw new \Exception('Model doesnt exist');

        return strpos($this->getTreePath(), $ancestor->getTreePath().$ancestor->getKey().'.')!==false and $ancestor->getTreePath()!==$this->getTreePath();
    }

    public function isAncestor($descendant)
    {
        if (!$this->exists) throw new \Exception('Model doesnt exist');

        return strpos($descendant->getTreePath(), $this->getTreePath().$this->getKey().'.')!==false and $descendant->getTreePath()!==$this->getTreePath();
    }

    public function isLeaf()
    {
        if (!$this->exists) throw new \Exception('Model doesnt exist');

        return !count($this->children(1)->get()->toArray());
    }

    public function relativeDepth($object)
    {
        return abs($this->getTreeDepth() - $object->getTreeDepth());
    }

    public static function allRoot()
    {
        $instance = new static;

        $query = $instance->newQuery()->where($instance->getColumnTreePid(), '=', 0);

        return $query;
    }

    public static function allLeaf()
    {
        $instance = with(new static);

        $query = $instance->newQuery();

        $query->select($instance->getTable().'.*');

        $query->leftJoin($instance->getTable().' as t_2', function($join) use ($instance)
        {
            $join->on($instance->getTable().'.'.$instance->getKeyName(), '=', 't_2.'.$instance->getColumnTreePid());
        })
            ->whereNull('t_2.id');

        return $query;
    }



    public function getTreePid()
    {
        return $this->getAttribute($this->getColumnTreePid());
    }

    public function getTreeOrder()
    {
        return $this->getAttribute($this->getColumnTreeOrder());
    }

    public function getTreePath()
    {
        return $this->getAttribute($this->getColumnTreePath());
    }

    public function getTreeDepth()
    {
        return $this->getAttribute($this->getColumnTreeDepth());
    }



    public function getColumnTreePid()
    {
        return $this->getTreeConfig("pid_column");
        //return $this->columnTreePid;
    }

    public function getColumnTreeOrder()
    {
        return $this->getTreeConfig("order_column");
        //return $this->columnTreeOrder;
    }

    public function getColumnTreePath()
    {
        return $this->getTreeConfig("path_column");
        //return $this->columnTreePath;
    }

    public function getColumnTreeDepth()
    {
        return $this->getTreeConfig("depth_column");
        //return $this->columnTreeDepth;
    }

//    public function setColumnTreePid($name)
//    {
//        $this->columnTreePid = $name;
//    }
//
//    public function setColumnTreeOrder($name)
//    {
//        $this->columnTreeOrder = $name;
//    }
//
//    public function setColumnTreePath($name)
//    {
//        $this->columnTreePath = $name;
//    }
//
//    public function setColumnTreeDepth($name)
//    {
//        $this->columnTreeDepth = $name;
//    }


    protected function processSiblingOf($sibling, $op)
    {
        if ($this->exists and $this->isAncestor($sibling)) throw new \Exception('Cant move Ancestor to Descendant');
        if (!$sibling->exists) throw new \Exception('Sibling doesnt exist');

        $this_ = &$this;

        $this->app['db']->transaction(function() use (&$this_, &$sibling, $op)
        {
            $sibling->sibling()->where($this_->getColumnTreeOrder(), $op, $sibling->getTreeOrder())->increment($this_->getColumnTreeOrder());

            if ($this_->exists)
            {
                $children = $this_->children()->get();

                foreach($children as $child)
                {
                    $child->update(array(
                        $child->getColumnTreePath() => str_replace($this_->getTreePath(), $sibling->getTreePath(), $child->getTreePath()),
                        $child->getColumnTreeDepth() => ( $sibling->getTreeDepth() + ($child->getTreeDepth() - $this_->getTreeDepth()) ),
                    ));
                }
            }

            $this_->fill(array(
                $this_->getColumnTreePath() => $sibling->getTreePath(),
                $this_->getColumnTreePid() => $sibling->getTreePid(),
                $this_->getColumnTreeOrder() => $sibling->getTreeOrder()+($op=='>'?1:0),
                $this_->getColumnTreeDepth() => $sibling->getTreeDepth(),
            ));

            $this_->save();
        });

        return $this;

    }//


    public  function getAllTree($where=null)
    {
        //check if root exists
        $root = self::where($this->getColumnTreePid(),0)->first();
        if (!$root)
        {
            $this->makeRoot();
        }
        if (!$where) {

            $coll = self::all();
        } else {

            $coll = self::query();
            foreach ($where as $values)
            {
                $this->applyFilterWhere($coll, $values);
            }
            $coll = $coll->get();
        }
        $tree = [];
        $tree = $this->processKids($tree,$coll);

        return $tree;


    }

    private function processKids(array &$tree, $collection, $parent_id = 0)
    {

        $branch = array();
        foreach ($collection as $element)
        {
            if ($element->getTreePid() == $parent_id)
            {
                $children = $this->processKids($tree, $collection, $element->id);
                $children = $this->app['skvn.crud']->sortArrayObjects($children,'tree_order');
                if ($children)
                {
                    $element->kids = $children;
                }
                $branch[$element->id] = $element;

            }
        }
        return $branch;
    }




    function saveTree($input)
    {
        if ($this->id == $this->rootId())
        {
            // no tree actions on root
            $this->fill($input);
            return $this->save();
        }

        $treeAction = (!empty($input['tree_action'])?$input['tree_action']:'');

        $oldParent = $this->getAttribute($this->getColumnTreePid());
        if (!isset($input[$this->getColumnTreePid()]))
        {
            $input[$this->getColumnTreePid()] = $this->rootId();
        }
        $parent = $input[$this->getColumnTreePid()];

        if (empty($parent))
        {
            $parent = $this->rootId();
        }

        if (!$this->exists)
        {
            $this->fillFromRequest($input);
            $this->moveTreeAction($parent,-1);
        } else {

            $this->fillFromRequest($input);
            if ($oldParent == $parent)
            {
                $this->save();
            } else {

                $this->moveTreeAction($parent, -1);
            }
        }
    }//


    function moveTreeAction($parent, $position)
    {

        $parentObj = self::find($parent);
        if ($position == 0)
        {
            $this->makeFirstChildOf($parentObj);
        }  else if ($position == -1)
        {
            $this->makeLastChildOf($parentObj);
        }
        else {
            $this->makeChildOf($parentObj, $position);
        }

        return true;
    }


    function getParents()
    {
        if (!$this->parents) {

            $p_ids = explode('.', $this->getTreePath());
            $this->parents = [];
            foreach ($p_ids as $id) {
                if (intval($id) > $this->rootId) {
                    $this->parents[] = $id;
                }
            }
            if (count($this->parents)) {
                $this->parents = self::find($this->parents);
            }
        }

        return $this->parents;
    }

    function rootId()
    {
        if (defined('static::ROOT_ID'))
        {
            return static :: ROOT_ID;
        }
        else if (defined('static::ROOT_ENV'))
        {
            return env(static :: ROOT_ENV);
        }
        else
        {
            return 1;
        }
    }
}