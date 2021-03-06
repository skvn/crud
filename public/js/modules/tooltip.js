;(function($, crud){
    var _tips = {};
    var _admin = false;

    crud.bind('crud.content_loaded', function(data){
        init_tooltips(data['cont']);
    });

    crud.bind('crud.submitted', function(data){
        var frm = $('#tooltip_edit');
        var idx = $('input[name=tt_index]', frm).val();
        var txt = $('textarea[name=tt_text]', frm).val();
        if (idx)
        {
            $('*[data-crud_tooltip_id='+idx+']').data('original-title', txt).attr('data-original-title', txt);
            _tips[idx] = txt;
        }
    });

    crud.bind('page.start', function(){
        init_tooltips();
    });


    function bind_admin(c)
    {
        c.off("dblclick").on("dblclick", function(e){
            e.preventDefault();
            var w = $('#tooltip_edit');
            $('input[name=tt_index]', w).val($(this).parent().data('crud_tooltip_id'));
            $('textarea[name=tt_text]', w).val($(this).parent().data('original-title'));
            w.modal('show');
        });
    }

    function init_tooltips(p)
    {
        p = p || $(crud.doc);
        var remote = [];
        $("*[data-crud_tooltip]", p).each(function(){
            var e = $(this);
            var prev = $(this).prev();
            var c = e.parent();
            c.data('placement', e.data('crud_tooltip_placement') || 'top').attr('data-placement', e.data('crud_tooltip_placement') || 'top');
            c.data('crud_tooltip_id', e.data('crud_tooltip')).attr('data-crud_tooltip_id', e.data('crud_tooltip'));
            c.data('original-title', e.data('crud_tooltip_text')).attr('data-original-title', e.data('crud_tooltip_text'));
            c.data('toggle', 'tooltip').attr('data-toggle', 'tooltip').data('html', 'true').attr('data-html', 'true');
            if (typeof(_tips[e.data('crud_tooltip')]) != "undefined")
            {
                c.data('original-title', _tips[e.data('crud_tooltip')]).attr('data-original-title', _tips[e.data('crud_tooltip')]);
            }
            else
            {
                if (e.data('crud_tooltip') != "dummy")
                {
                    remote.push(e.data('crud_tooltip'));
                }
            }
            if (e.data('crud_tooltip') != "dummy" && _admin)
            {
                bind_admin(prev);
                bind_admin(e);
            }
            c.tooltip({container: 'body'});
        });
        if (remote.length)
        {
            $.get("/admin/util/tooltip/fetch", {ids: remote}, function(res){
                if (!res['tips'])
                {
                    return;
                }
                var elm;

                for (idx in res['tips'])
                {
                    if (res['tips'][idx])
                    {
                        _tips[idx] = res['tips'][idx];
                    }
                    elm = $('*[data-crud_tooltip='+idx+']');
                    if (elm.length)
                    {
                        if (res['tips'][idx])
                        {
                            elm.parent().data('original-title', res['tips'][idx]).attr('data-original-title', res['tips'][idx]);

                        }
                        if (res['allow_edit'])
                        {
                            _admin = true;
                            bind_admin(elm.prev());
                            bind_admin(elm);
                        }
                    }
                }
            });
        }
    }

})($, CRUD);