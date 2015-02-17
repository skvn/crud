;(function($, CRUD){
    var _tips = {};
    var _admin = false;
    $(document).bind('crud.content_loaded', function(e, data){
        init_tooltips(data['cont']);
    });
    $(document).bind('ajax_form.return', function(e, data){
        var frm = $('#tooltip_edit');
        var idx = $('input[name=tt_index]', frm).val();
        var txt = $('textarea[name=tt_text]', frm).val();
        $('*[data-crud_tooltip_id='+idx+']').data('original-title', txt).attr('data-original-title', txt);
        _tips[idx] = txt;
    });

    $(function(){
        init_tooltips();
    });

    function init_tooltips(p)
    {
        p = p || $(document);
        var remote = [];
        $("*[data-crud_tooltip]", p).each(function(){
            var e = $(this);
            var c = e.parent();
            c.data('placement', e.data('crud_tooltip_placement') || 'top').attr('data-placement', e.data('crud_tooltip_placement') || 'top');
            c.data('crud_tooltip_id', e.data('crud_tooltip')).attr('data-crud_tooltip_id', e.data('crud_tooltip'));
            if (e.data('crud_tooltip_text'))
            {
                c.data('original-title', c.data('crud_tooltip_text')).attr('data-original-title', e.data('crud_tooltip_text'));
                c.data('toggle', 'tooltip').attr('data-toggle', 'tooltip').data('html', 'true').attr('data-html', 'true');
            }
            else if (typeof(_tips[e.data('crud_tooltip')]) != "undefined")
            {
                c.data('original-title', _tips[e.data('crud_tooltip')]).attr('data-original-title', _tips[e.data('crud_tooltip')]);
                c.data('toggle', 'tooltip').attr('data-toggle', 'tooltip').data('html', 'true').attr('data-html', 'true');
                if (_admin)
                {
                    c.off("dblclick").on("dblclick", function(e){
                        e.preventDefault();
                        var w = $('#tooltip_edit');
                        $('input[name=tt_index]', w).val($(this).data('crud_tooltip_id'));
                        $('textarea[name=tt_text]', w).val($(this).data('original-title'));
                        w.modal('show');
                    });
                }
            }
            else
            {
                remote.push(e.data('crud_tooltip'));
            }
        });
        if (remote.length)
        {
            $.get("/util/tooltip/fetch", {ids: remote}, function(res){
                if (!res['tips'])
                {
                    return;
                }
                var elm;
                for (idx in res['tips'])
                {
                    _tips[idx] = res['tips'][idx];
                    elm = $('*[data-crud_tooltip='+idx+']');
                    if (elm.length)
                    {
                        elm.parent().data('original-title', res['tips'][idx]).attr('data-original-title', res['tips'][idx]);
                        elm.parent().data('toggle', 'tooltip').attr('data-toggle', 'tooltip').data('html', 'true').attr('data-html', 'true');
                        elm.parent().data('crud_tooltip_id', idx).attr('data-crud_tooltip_id', idx);
                        if (res['allow_edit'])
                        {
                            _admin = true;
                            elm.parent().off("dblclick").on("dblclick", function(e){
                                e.preventDefault();
                                var w = $('#tooltip_edit');
                                $('input[name=tt_index]', w).val($(this).data('crud_tooltip_id'));
                                $('textarea[name=tt_text]', w).val($(this).data('original-title'));
                                w.modal('show');
                            });
                        }
                    }
                }
            });
        }
    }

})($, CRUD);