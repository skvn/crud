<?php namespace Skvn\Crud\Traits;


trait ModelRelationTrait
{

    protected $dirtyRelations = [];


    //protected $processableRelations = [];

    static function bootModelRelationTrait()
    {
//        static :: registerSetter(function ($instance, $key, $value){
//            return $instance->relationSetValue($key, $value);
//        });

        static :: saved(function ($instance){
            if ($instance->eventsDisabled)
            {
                return true;
            }
            $instance->saveRelations();
        });
    }

//    protected function relationSetValue($key, $value)
//    {
//        $col = $this->config['fields'][$key] ?? [];
//        if (!empty($col['relation']))
//        {
//            $this->dirtyRelations[$key] = $value;
//            return true;
//        }
//    }

//    public function saveRelations()
//    {
//        if ($this->dirtyRelations  && is_array($this->dirtyRelations ))
//        {
//            foreach ($this->dirtyRelations as $k => $v)
//            {
//                $field = $this->getField($k);
//                switch ($field['relation'])
//                {
//                    case "hasOne":
//                        $relObj = self :: createInstance($field['model'], null, $v);
//                        $relObj->setAttribute($field['ref_column'], $this->getKey());
//                        $relObj->save();
//                        break;
//
//                    case "hasMany":
//                        if (is_array($v))
//                        {
//                            $oldIds = $this->$k()->lists('id')->toArray();
//                            foreach ($v as $id)
//                            {
//                                $obj = self :: createInstance($field['model'], null, $id);
//                                $this->$k()->save($obj);
//                            }
//                            $toUnlink = array_diff($oldIds, $v);
//                        }
//                        else
//                        {
//                            $toUnlink = $this->$k()->lists('id')->toArray();
//                        }
//
//                        if ($toUnlink && is_array($toUnlink))
//                        {
//                            foreach ($toUnlink as $id)
//                            {
//                                if (!empty($field['ref_column']))
//                                {
//                                    $col = $field['ref_column'];
//                                }
//                                else
//                                {
//                                    $col = $this->classViewName . '_id';
//                                }
//                                $obj = self :: createInstance($field['model'], null, $id);
//                                if (($field['on_delete'] ?? "set_null") === "delete")
//                                {
//                                    $obj->delete();
//                                }
//                                else
//                                {
//                                    $obj->$col = null;
//                                    $obj->save();
//                                }
//                            }
//                        }
//
//                        break;
//                    case "belongsToMany":
//                        if (is_array($v))
//                        {
//                            $this->$k()->sync($v);
//                        }
//                        else
//                        {
//                            $this->$k()->sync([]);
//                        }
//
//                        break;
//                }
//
//            }
//        }
//        $this->dirtyRelations = null;
//    }//

//    private function createCrudRelation($relAttributes, $method)
//    {
//        $relType = $relAttributes['relation'];
//        switch ($relType)
//        {
//            case self::RELATION_BELONGS_TO:
//                return $this->$relType(self :: resolveClass($relAttributes['model']), $relAttributes['field'], null, $method);
//
//            case self::RELATION_HAS_ONE:
//                $ref_col = (!empty($relAttributes['ref_column'])?$relAttributes['ref_column']:null);
//                return $this->$relType(self :: resolveClass($relAttributes['model']),  $ref_col);
//
//            case self::RELATION_BELONGS_TO_MANY:
//                $pivot_table = (!empty($relAttributes['pivot_table'])?$relAttributes['pivot_table']:null);
//                $pivot_self_column = (!empty($relAttributes['pivot_self_key'])?$relAttributes['pivot_self_key']:null);
//                $pivot_foreign_column = (!empty($relAttributes['pivot_foreign_key'])?$relAttributes['pivot_foreign_key']:null);
//                $rel = $this->$relType(self :: resolveClass($relAttributes['model']), $pivot_table, $pivot_self_column, $pivot_foreign_column, $method);
//                return $this->sortRelation($rel, $relAttributes);
//
//            case self::RELATION_HAS_MANY:
//                $ref_col = (!empty($relAttributes['ref_column'])?$relAttributes['ref_column']:null);
//                $rel = $this->$relType(self :: resolveClass($relAttributes['model']), $ref_col );
//                return $this->sortRelation($rel, $relAttributes);
//
//            default:
//                return $this->$relType(self :: resolveClass($relAttributes['model']));
//
//
//        }
//
//    }

//    function sortRelation($relation, $relConfig)
//    {
//        if (!empty($relConfig['sort']))
//        {
//            foreach ($relConfig['sort'] as $col => $dir)
//            {
//                $relation->orderBy($col, $dir);
//            }
//        }
//
//        return $relation;
//    }
//    function resolveListRelation($alias)
//    {
//        if (strpos($alias,'::') !== false)
//        {
//            return explode('::',$alias);
//        }
//        return false;
//    }

//    public function getRelationIds($relation)
//    {
//        $data = $this->$relation->lists('id');
//        if (is_object($data) && ($data instanceof Collection))
//        {
//            $data = $data->all();
//        }
//
//        return $data;
//    }

//    function isManyRelation($relation)
//    {
//        return in_array($relation, ['hasMany','belongsToMany', 'morphToMany', 'morphedByMany']);
//    }

//    function getCrudRelation($name)
//    {
//        if (array_key_exists($name, $this->config['fields'] ?? []))
//        {
//            $col = $this->getField($name);
//            if (!empty($col['relation']))
//            {
//                return $col;
//            }
//        }
//        return false;
//    }



//    function processRelationsOndelete()
//    {
//
//        foreach ($this->config['fields'] ?? [] as $fname=>$field)
//        {
//            if (!empty($field['relation']) && $field['relation'] == 'hasMany' && !empty($field['on_delete'])) {
//
//
//                if (!empty($field['ref_column'])) {
//                    $col = $field['ref_column'];
//                }  else {
//                    $col = $this->classViewName . '_id';
//                }
//                $relObjects = $this->$fname;
//                $relObjects->each(function ($item, $key) use ($field, $col) {
//
//                    if ($field['on_delete']  === "delete")
//                    {
//
//                        $item->delete();
//                    }
//                    else
//                    {
//                        $item->$col = null;
//                        $item->save();
//                    }
//                });
//            } else if (!empty($field['relation']) && $field['relation'] == 'hasOne' && !empty($field['on_delete'])) {
//
//                if ($field['on_delete']  === "delete")
//                {
//                    $this->$fname->delete();
//                }
//                else
//                {
//                    $col = $field['field'];
//                    $this->$fname->$col = null;
//                    $this->$fname->save();
//                }
//
//            }
//
//        }
//    }
}