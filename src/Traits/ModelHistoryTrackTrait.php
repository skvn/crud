<?php

namespace Skvn\Crud\Traits;

trait ModelHistoryTrackTrait
{
    public static function bootModelHistoryTrackTrait()
    {
        static :: updated(function ($instance) {
            //            if ($instance->eventsDisabled)
//            {
//                return true;
//            }
            $instance->processHistoryEvent('update');
        });
        static :: created(function ($instance) {
            //            if ($instance->eventsDisabled)
//            {
//                return true;
//            }
            $instance->processHistoryEvent('create');
        });
        static :: deleted(function ($instance) {
            //            if ($instance->eventsDisabled)
//            {
//                return true;
//            }
            $instance->processHistoryEvent('delete');
        });
    }

    protected function processHistoryEvent($event_type)
    {
        if (empty($this->config['track_history'])) {
            return;
        }
        $track_cols = array_filter($this->getFieldsByField(), function ($item) {
            if (!empty($item['track_history'])) {
                return true;
            }
        });
        $dirty = $this->getDirty();
        $changes = [];
        foreach ($dirty as $k => $v) {
            if (isset($track_cols[$k])) {
                $changes[] = [
                    'field_name'      => $k,
                    'field_value'     => $v,
                    'field_old_value' => $this->getOriginal($k),
                    'date_modified'   => time(),
                    'model'           => $this->classShortName,
                    'ref_id'          => $this->id,
                    'modified_by'     => $this->app['auth']->check() ? $this->app['auth']->user()->id : 0,
                ];
            }
        }
        if (empty($changes) && $event_type == 'delete') {
            foreach (array_keys($track_cols) as $col) {
                $changes[] = [
                    'field_name'      => $col,
                    'field_value'     => null,
                    'field_old_value' => $this->getOriginal($col),
                    'date_modified'   => time(),
                    'model'           => $this->classShortName,
                    'ref_id'          => $this->id,
                    'modified_by'     => $this->app['auth']->check() ? $this->app['auth']->user()->id : 0,
                ];
            }
        }
        if (empty($changes)) {
            return;
        }
        if ($this->config['track_history'] == 'detail') {
            foreach ($changes as $change) {
                $change = $this->prepareHistoryEvent($change, $event_type);
                $this->recordHistoryEvent($change, $event_type);
            }

            return;
        }
        if ($this->config['track_history'] == 'summary') {
            $first = array_shift($changes);
            $event = [
                'field_name'      => null,
                'field_value'     => [$first['field_name'] => $first['field_value']],
                'field_old_value' => [$first['field_name'] => $first['field_old_value']],
                'date_modified'   => $first['date_modified'],
                'model'           => $first['model'],
                'ref_id'          => $first['ref_id'],
                'modified_by'     => $first['modified_by'],
            ];
            foreach ($changes as $change) {
                $event['field_value'][$change['field_name']] = $change['field_value'];
                $event['field_old_value'][$change['field_name']] = $change['field_old_value'];
            }
            $event['field_value'] = json_encode($event['field_value'], JSON_UNESCAPED_UNICODE);
            $event['field_old_value'] = json_encode($event['field_old_value'], JSON_UNESCAPED_UNICODE);
            $event = $this->prepareHistoryEvent($event, $event_type);
            $this->recordHistoryEvent($event, $event_type);

            return;
        }
    }

    protected function prepareHistoryEvent($event, $event_type = null)
    {
        return $event;
    }

    protected function recordHistoryEvent($event, $event_type = null)
    {
        $this->app['db']->table('crud_history_trait')->insert($event);
    }
}
