<?php namespace Skvn\Crud\Traits;


trait HistoryTrackTrait {


    protected  function onAfterSave()
    {

        $track_cols = array_filter($this->config->getFields(), function ($item) { if (!empty($item['track'])) return true;});
        $dirty = $this->getDirty();
        foreach ($dirty as $k=>$v)
        {
            if (isset($track_cols[$k]))
            {
                $this->app['db']->table('crud_history_track')->insert(
                    [
                        'field_name' => $k,
                        'field_value' => $v,
                        'date_modified' => time(),
                        'model' => $this->classShortName,
                        'ref_id' => $this->id,
                        'modified_by' => $this->app['auth']->check()?$this->app['auth']->user()->id:0,
                    ]
                );
            }
        }

        parent::onAfterSave();
    }

}