<?php namespace Skvn\Crud\Traits;


trait ModelRelationTrait
{

    protected $dirtyRelations = [];
    protected $crudRelations = [];
    protected $processableRelations = [];

    public function saveRelations()
    {
        $formConf = $this->getFields();

        if ($this->dirtyRelations  && is_array($this->dirtyRelations ))
        {
            $form = $this->getForm($this->dirtyRelations, true);

            foreach ($this->dirtyRelations as $k => $v)
            {
                if (!empty($form->fields[$k]))
                {
                    $v = $form->fields[$k]->getValueForDb();
                }

                switch ($this->crudRelations[$k])
                {

                    case self :: RELATION_HAS_ONE:
                        $class = self :: resolveClass($formConf[$k]['model']);
                        $relObj = $class::find($v);
                        $relObj->setAttribute($formConf[$k]['ref_column'], $this->id);
                        $relObj->save();
                        break;

                    case self :: RELATION_HAS_MANY:
                        $class = self :: resolveClass($formConf[$k]['model']);
                        if (is_array($v))
                        {
                            $oldIds = $this->$k()->lists('id');
                            foreach ($v as $id) {

                                $obj = $class::find($id);
                                $this->$k()->save($obj);
                            }
                            $toUnlink = array_diff($oldIds, $v);

                        }
                        else
                        {
                            $toUnlink = $this->$k()->lists('id');
                        }

                        if ($toUnlink && is_array($toUnlink))
                        {
                            foreach ($toUnlink as $id)
                            {
                                if (!empty($formConf[$k]['ref_column']))
                                {
                                    $col = $formConf[$k]['ref_column'];
                                }
                                else
                                {
                                    $col = snake_case($this->classShortName . 'Id');
                                }
                                $obj = $class::find($id);
                                $obj->$col = null;
                                $obj->save();
                            }
                        }

                        break;
                    case self :: RELATION_BELONGS_TO_MANY:
                        if (is_array($v))
                        {
                            $this->$k()->sync($v);
                        }
                        else
                        {
                            $this->$k()->sync([]);
                        }
                        //$this->load($k);

                        break;
                }

            }
        }
        $this->dirtyRelations = null;
    }//

    private function createCrudRelation($relType, $relAttributes, $method)
    {
        switch ($relType)
        {
            case self::RELATION_BELONGS_TO:
                //return $this->$relType('\App\Model\\'.$relAttributes['model'],$relAttributes['column_index'], null, $method);
                return $this->$relType(self :: resolveClass($relAttributes['model']), $relAttributes['column_index'], null, $method);
                break;

            case self::RELATION_HAS_ONE:
                $ref_col = (!empty($relAttributes['ref_column'])?$relAttributes['ref_column']:null);
                return $this->$relType(self :: resolveClass($relAttributes['model']),  $ref_col);
                break;

            case self::RELATION_BELONGS_TO_MANY:
                //return $this->$relType('\App\Model\\'.$relAttributes['model'],null, null, null, $method);
                $pivot_table = (!empty($relAttributes['pivot_table'])?$relAttributes['pivot_table']:null);
                $pivot_self_column = (!empty($relAttributes['pivot_self_key'])?$relAttributes['pivot_self_key']:null);
                $pivot_foreign_column = (!empty($relAttributes['pivot_foreign_key'])?$relAttributes['pivot_foreign_key']:null);
                return $this->$relType(self :: resolveClass($relAttributes['model']), $pivot_table, $pivot_self_column, $pivot_foreign_column, $method);
                break;

            case self::RELATION_HAS_MANY:
                $ref_col = (!empty($relAttributes['ref_column'])?$relAttributes['ref_column']:null);
                return $this->$relType(self :: resolveClass($relAttributes['model']), $ref_col );
                break;

            default:
                return $this->$relType(self :: resolveClass($relAttributes['model']));
                break;


        }

    }



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

    public function getRelationIds($relation)
    {
        $data = $this->$relation->lists('id');
        if (is_object($data) && ($data instanceof Collection))
        {
            $data = $data->all();
        }

        return $data;
    }


}