<?php

namespace Skvn\Crud\Models;

class CrudNotify extends CrudModel
{
    protected $table = 'crud_notify';
    protected $guarded = ['id'];

    public static function post($args = [])
    {
        if (empty($args['message'])) {
            throw new \Exception('Crud::Notify message is not set');
        }
        if (!empty($args['broadcast']) && empty($args['ttl'])) {
            throw new \Exception('Crud::Notify broadcast message must have time to live');
        }
        if (empty($args['created_by'])) {
            if (app()['auth']->check()) {
                $args['created_by'] = app()['auth']->user()->id;
            }
        }

        return static :: create($args);
    }

    public static function postFor($message, $user_id, $args = [])
    {
        $args['message'] = $message;
        $args['target_user_id'] = $user_id;

        return static :: post($args);
    }

    public static function postBroadcast($message, $ttl = 1, $args = [])
    {
        $args['message'] = $message;
        $args['broadcast'] = 1;
        $args['ttl'] = $ttl;

        return static :: post($args);
    }

    public static function fetchNext($validator = null)
    {
        $uid = 0;
        if (app()['auth']->check()) {
            $uid = app()['auth']->user()->id;
        }
        $received = app()['session']->get('crud_notify');
        if (empty($received)) {
            $received = [];
        }
        $list = app()['db']->table('crud_notify')->where('delivered', '=', 0)->orderBy('id', 'asc')->get();
        $notify = null;
        foreach ($list as $entry) {
            if ($entry->broadcast && time() > ($entry->created_at + $entry->ttl * 3600)) {
                app()['db']->table('crud_notify')->where('id', '=', $entry->id)->update(['delivered' => 1]);
                continue;
            }
            if ($entry->target_user_id > 0) {
                if ($uid && $uid == $entry->target_user_id) {
                    $notify = $entry;
                    break;
                } else {
                    continue;
                }
            }
            if (in_array($entry->id, $received)) {
                continue;
            }
            if (is_callable($validator)) {
                if (!$validator($entry)) {
                    continue;
                }
            }
            $notify = $entry;
            break;
        }
        if (!empty($notify)) {
            if ($notify->target_user_id || !$notify->broadcast) {
                app()['db']->table('crud_notify')->where('id', '=', $notify->id)->update(['delivered' => 1, 'delivered_at' => time()]);
            } else {
                app()['db']->table('crud_notify')->where('id', '=', $notify->id)->update(['delivered_at' => time()]);
                $received[] = $entry->id;
            }
            $notify->title = 'Уведомление';
        }
        app()['session']->set('crud_notify', $received);

        return $notify;
    }
}
