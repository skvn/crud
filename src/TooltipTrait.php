<?php namespace LaravelCrud;

trait TooltipTrait
{
    function crudTooltipFetch()
    {
        $helper = \App :: make('CmsHelper');

        $ids = \Input :: get('ids');
        $tooltips = \DB :: table('crud_tooltip')->whereIn('tt_index', $ids)->get();


        $data = ['allow_edit' => $helper->checkAcl(\Config :: get('crud.crud_tooltip.acl')), 'tips' => []];
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
        $helper = \App :: make('CmsHelper');
        if (!$helper->checkAcl(\Config :: get('crud.crud_tooltip.acl')))
        {
            return \Response('Access denied',403);
        }
        $id = \Input :: get('tt_index');
        if (!empty($id))
        {
            $t = \DB :: table("crud_tooltip")->where('tt_index', $id)->first();
            if ($t && !empty($t->id))
            {
                \DB :: table("crud_tooltip")->where('tt_index', $id)->update(['tt_text' => \Input :: get('tt_text')]);
            }
            else
            {
                \DB :: table("crud_tooltip")->insert(['tt_index' => $id, 'tt_text' => \Input :: get('tt_text')]);
            }
        }
        return ['success' => true];
    }
}