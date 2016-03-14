<?php namespace Skvn\Crud\Form;


use Skvn\Crud\CrudConfig;

class Tags extends Field {


    function getValue()
    {
        if (!$this->value)
        {
            $class = app()['skvn.crud']->getModelClass($this->config['model']);
            $dummyModel = new $class();
            $ids = $this->form->crudObj->getRelationIds($this->getName());
            if (count($ids)) {
                $collection = $class::find($ids);
                $this->value = $collection->pluck($dummyModel->config->get('title_field'));
            }
        }

        return $this->value;
    }

    function getValueForDb()
    {
        if ($this->value) {

            $split = explode(',',$this->value);
            $class = app()['skvn.crud']->getModelClass($this->config['model']);
            $dummyModel = new $class();
            $ids = [];
            foreach ($split as $title) {
                $title = trim($title);
                $obj = $class::firstOrCreate([$dummyModel->config->get('title_field') => $title]);
                $ids[] = $obj->id;
            }


            return $ids;
        }
    }


} 