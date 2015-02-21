<?php namespace LaravelCrud\Model;


class CrudNotify extends CrudModel{



    protected $table = 'crud_notify';
    protected $guarded = array('id');

    static function post($args = [])
    {
        if (empty($args['message']))
        {
            throw new \Exception('Crud::Notify message is not set');
        }
        if (!empty($args['broadcast']) && empty($args['ttl']))
        {
            throw new \Exception('Crud::Notify broadcast message must have time to live');
        }
        if (empty($args['created_by']))
        {
            if (\Auth :: check())
            {
                $args['created_by'] = \Auth :: user()->id;
            }
        }
        return static :: create($args);
    }

    static function postFor($message, $user_id, $args = [])
    {
        $args['message'] = $message;
        $args['target_user_id'] = $user_id;
        return static :: post($args);
    }

    static function postBroadcast($message, $ttl = 1, $args = [])
    {
        $args['message'] = $message;
        $args['broadcast'] = 1;
        $args['ttl'] = $ttl;
        return static :: post($args);
    }

    static function fetchNext($validator = null)
    {
        $uid = 0;
        if (\Auth :: check())
        {
            $uid = \Auth :: user()->id;
        }
        $received = \Session :: get('crud_notify');
        if (empty($received))
        {
            $received = [];
        }
        $list = \DB :: table('crud_notify')->where('delivered', '=', 0)->orderBy('id', 'asc')->get();
        $notify = null;
        foreach ($list as $entry)
        {
            if ($entry->broadcast && time() > ($entry->created_at + $entry->ttl * 3600))
            {
                \DB :: table('crud_notify')->where('id', '=', $entry->id)->update(['delivered' => 1]);
                continue;
            }
            if ($entry->target_user_id > 0)
            {
                if ($uid && $uid == $entry->target_user_id)
                {
                    $notify= $entry;
                    break;
                }
                else
                {
                    continue;
                }
            }
            if (in_array($entry->id, $received))
            {
                continue;
            }
            if (is_callable($validator))
            {
                if (!$validator($entry))
                {
                    continue;
                }
            }
            $notify = $entry;
            break;
        }
        if (!empty($notify))
        {
            if ($notify->target_user_id || !$notify->broadcast)
            {
                \DB :: table('crud_notify')->where('id', '=', $notify->id)->update(['delivered' => 1, 'delivered_at' => time()]);
            }
            else
            {
                \DB :: table('crud_notify')->where('id', '=', $notify->id)->update(['delivered_at' => time()]);
                $received[] = $entry->id;
            }
            $notify->title = "Уведомление";
        }
        \Session :: set('crud_notify', $received);
        return $notify;
    }






}