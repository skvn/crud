<?php namespace LaravelCrud;

trait TooltipTrait
{
    function crudTooltipFetch()
    {

        $ids = $this->app['request']->get('ids');
        $tooltips = $this->app['db']->table('crud_tooltip')->whereIn('tt_index', $ids)->get();


        $data = ['allow_edit' => $this->app['skvn.cms']->checkAcl($this->app['config']->get('crud.crud_tooltip.acl')), 'tips' => []];
        foreach ($tooltips as $tooltip)
        {
            $data['tips'][$tooltip->tt_index] = $tooltip->tt_text;
        }
        foreach ($ids as $id)
        {
            if (!array_key_exists($id, $data['tips']))
            {
                $data['tips'][$id] = "";
            }
        }
        return $data;
    }

    function crudTooltipUpdate()
    {
        if (!$this->app['skvn.cms']->checkAcl(\Config :: get('crud.crud_tooltip.acl')))
        {
            return ['success' => false, 'message' => "Access denied"];
        }
        $id = $this->app['request']->get('tt_index');
        if (!empty($id))
        {
            $t = $this->app['db']->table("crud_tooltip")->where('tt_index', $id)->first();
            if ($t && !empty($t->id))
            {
                $this->app['db']->table("crud_tooltip")->where('tt_index', $id)->update(['tt_text' => \Input :: get('tt_text')]);
            }
            else
            {
                $this->app['db']->table("crud_tooltip")->insert(['tt_index' => $id, 'tt_text' => \Input :: get('tt_text')]);
            }
        }
        return ['success' => true];
    }
}