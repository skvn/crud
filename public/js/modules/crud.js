;(function(w, d, l, c, $){

    var listeners = {};
    var events = {};
    var settings = {
        model_edit_url: '/admin/crud/{model}/edit/{id}?scope={scope}',
        model_filter_url: '/admin/crud/{model}/filter/{scope}',
        model_list_url: '/admin/crud/{model}/list/{scope}{uri_params}',
        model_delete_url: '/admin/crud/{model}/delete',
        model_move_tree_url: '/admin/crud/{model}/move_tree',
        model_tree_url: '/admin/crud/{model}/tree/{scope}',
        model_tree_options_url: '/admin/crud/{model}/tree_options?id={id}&field={field}',
        model_autocomplete_url: '/admin/crud/{model}/autocomplete/{scope}',
        model_command_url: '/admin/crud/{model}/{id}/command/{command}?scope={scope}',

    };
    var crud_actions = {};
    var i18n = w.i18n;


    w.CRUD = w.CRUD || {
            win: w,
            doc: d,
            loc: l,
            con: c,
            //crudObj : w.crud_object_conf,
            bind: function(event, listener)
            {
                if (typeof(listeners[event]) == "undefined")
                {
                    listeners[event] = [];
                }
                listeners[event].push(listener);
                if (typeof(events[event]) != "undefined")
                {
                    listener(events[event]);
                }
            },
            trigger : function(event, data)
            {
                if (typeof(data) == "undefined")
                {
                    data = {};
                }
                if (typeof(listeners[event]) != "undefined")
                {
                    for (var i=0; i<listeners[event].length; i++)
                    {
                        listeners[event][i](data);
                    }
                }
                events[event] = data;
            },
            format_setting: function(key, args)
            {
                var str = settings[key] || "";
                for (var i in args)
                {
                    str = str.replace('{' + i + '}', args[i] || "");
                }
                return str;
            },
            set_config: function(config)
            {
                for (var i in config)
                {
                    settings[i] = config[i];
                }
            },
            add_actions: function(actions)
            {
                for (var i in actions)
                {
                    crud_actions[i] = actions[i];
                }
            },
            getHash: function (str, asString, seed){
                /*jshint bitwise:false */
                var i, l,
                    hval = (seed === undefined) ? 0x811c9dc5 : seed;

                for (i = 0, l = str.length; i < l; i++) {
                    hval ^= str.charCodeAt(i);
                    hval += (hval << 1) + (hval << 4) + (hval << 7) + (hval << 8) + (hval << 24);
                }
                if( asString ){
                    // Convert to 8 digit hex string
                    return ("0000000" + (hval >>> 0).toString(16)).substr(-8);
                }
                return hval >>> 0;
            },

            init_edit_tab: function(model, id, args)
            {
                args = args || {};
                var scope = args.scope || "";
                var $tab_cont = args.table.parents('div.tabs-container').first();
                if ( $('a[href=#tab_'+model+'_'+scope+'_'+id+']',$tab_cont).length)
                {
                    $('a[href=#tab_'+model+'_'+scope+'_'+id+']',$tab_cont).first().click();
                    return;
                }
                var self = this;
                var $tpl_tab =  $tab_cont.find('ul.nav-tabs li[data-edit_tab_tpl=1]').clone(true).removeAttr('data-edit_tab_tpl');


                var rel = model+'_'+scope+'_'+id;
                var id_label = (parseInt(id)<0)? 'Новая запись': id;
                $tpl_tab.find('a').html($tpl_tab.find('a').html().replace("[ID]",'['+id_label+']').replace('[REL]', rel));
                $tpl_tab.appendTo($tab_cont.find('ul.nav-tabs').first())
                    .show()
                    .find('a').first()
                    .data('id', id).attr('data-id', id)
                    .attr('href','#tab_'+rel);

                var rargs = args.rargs?args.rargs:{};
                var url = this.format_setting("model_edit_url", {model: model, id: id, scope:scope});

                $tab_cont.addClass('veiled');
                $.get(url, rargs, function (res){

                    var $cont = $(res);
                    $cont.appendTo($tab_cont.find('div.tab-content').first());
                    $tpl_tab.find('div.sk-spinner').hide();
                    $tpl_tab.find('a').show().first().click();
                    $tab_cont.removeClass('veiled');
                    var frm = $cont.find('form').first();
                    frm.crud_form();
                    var handler = 'onShow_' + frm.data('crud_model') + '_' + frm.data('crud_scope');
                    if (crud_actions[handler])
                    {
                        crud_actions[handler]($cont);
                    }
                    self.trigger('crud.content_loaded', {cont: $cont});

                });
            },
            init_modal: function(model, id, args)
            {

                args = args || {};
                var scope = args['scope'] || '';
                var url = this.format_setting("model_edit_url", {model: model, id: id, scope:scope});

                $('#crud_form').html('');
                if (!$('#crud_form').is(':visible')) {
                    $('#crud_form').modal('show');
                }
                var self = this;
                $('#crud_form').load(url, function (res)
                {
                    if (res == 'Access denied')
                    {
                        alert('Недостаточно прав доступа');
                        $('#crud_form').modal('hide');
                        return;
                    }
                    var frm = $('form:first', $('#crud_form'));
                    frm.crud_form();
                    var handler = 'onShow_' + frm.data('crud_model') + '_' + frm.data('crud_scope');
                    if (crud_actions[handler])
                    {
                        crud_actions[handler]($('#crud_form'));
                    }
                    self.trigger('crud.content_loaded', {cont: $('#crud_form')});

                });


            },
            format_error: function(error)
            {
                if (error.indexOf("SQLSTATE[23000]")>=0)
                {
                    return i18n.say('error_duplicate');
                }
                else
                {
                    return i18n.say('error_occured')+': ' + error;
                }
            },

            action: function  (elm, action)
            {
                return  crud_actions[action](elm);
            }

        };

    var crud = w.CRUD;
    crud_actions = {
        call_uri: function(elem)
        {
            $.get(elem.data('uri'), $.extend({}, elem.data()), function(res){
                alert(res['message']);
            });
        },
        crud_command: function(elem)
        {
            var args = elem.data('args') || {};

            args['command'] = elem.attr('href');
            args['id'] = parseInt(elem.data('id'))>0?parseInt(elem.data('id')):-1;
            args['model'] = elem.data('model');
            args['scope'] = elem.data('scope');
            $("input,select", elem.parents("form")).each(function(){
                args[$(this).attr('name')] = $(this).val();
            });
            
            var com_url = crud.format_setting("model_command_url", args );
            var $tbl = $('table[data-list_table_ref='+args['model']+'_'+args['scope']+']');
            
            if (args['id']<0)
            {

                var rows =[];
                var selected_objs =  $tbl.DataTable().rows('.selected').data();
                for (var i = 0; i < selected_objs.length; i++) {
                    rows.push(selected_objs[i]);
                }

                args['selected_rows'] = rows;
            } else {
                args['selected_row'] = $tbl.DataTable().row(elem.parents('tr').first()).data();
            }


            $.post(com_url, args, function (res)
            {
                if (res.success)
                {
                    if (res.message)
                    {
                        alert(res.message);
                    }
                    if (elem.data('callback_event'))
                    {
                        res['elem'] = elem;
                        crud.trigger(elem.data('callback_event'), res);
                    }
                    else if (elem.data('callback'))
                    {
                        eval(elem.data('callback'));
                    }
                    else
                    {
                        crud.trigger('crud.reload', res);
                    }
                }
                else
                {
                    alert(res.error);
                }

            }, 'json').fail(function (res) {
                alert(res.statusText)
            });

        },
        open_popup: function(elem)
        {

            var url = "";
            if (elem.data('uri')){

                url = elem.data('uri');
            } else if (elem.attr('href')){

                url = elem.attr('href');
            }

            var popup = elem.data('popup');

            if (!popup) {
                popup = crud.getHash(url, true);
            }
            // if ($('#' + popup).length <= 0) {
            //     $('<div id="' + popup + '" style="display: none;"></div>').appendTo($(crud.doc.body));
            // }
            if (url != '' && url != '#') {

                $.get(url, $.extend({}, elem.data()), function (res) {

                    $new_content = $(res);
                    $new_content.attr('id', popup);
                    if ($('#' + popup).length <= 0) {
                        
                        $new_content.appendTo($(crud.doc.body));
                    } else {
                        $('#' + popup).replaceWith($new_content);
                    }
                    var w = $('#' + popup);

                    crud.trigger('crud.content_loaded', {cont: w});
                    w.modal({keyboard: false, show: true, backdrop: 'static'});
                    if (elem.data('title')) {
                        $("h4", w).html(elem.data("title"));
                    }
                    if (crud_actions['onShow_' + popup]) {
                        crud_actions['onShow_' + popup]($('#' + popup));
                    }
                    //if (elem.data("onshow")) {
                    //    switch (elem.data("onshow")) {
                    //        case 'pass_row_ids':
                    //            var ids = [];
                    //            $('table input[data-rel=row]').each(function () {
                    //                if ($(this).prop('checked')) {
                    //                    ids.push($(this).attr('value'));
                    //                }
                    //            });
                    //            $('input[name=row_ids]', w).val(ids.join(','));
                    //            break;
                    //    }
                    //}
                    //crud.init_ichecks(w);
                    if ($("form", w).attr('id') != 'crud_filter_form')
                    {
                        $("form", w).crud_form();
                    }
                });
            } else {
                alert('Crud popup url not defined!');
            }
        },
        remove_parent_row: function(elem)
        {
            elem.parents("tr:first").remove();
        }

    };
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(crud.doc).ajaxError(function(e, xhr){
        if (xhr.status == 401)
        {
            alert('Unauthorized');
            crud.loc.reload();
        }
    });
    $(crud.doc).on('click','*[data-click]', function (e){
            e.preventDefault();
            handle_action($(this),$(this).data('click') )

        }
    );
    $(crud.doc).on('change','*[data-change]', function (e){
            e.preventDefault();
            handle_action($(this),$(this).data('change') )

        }
    );

    function handle_action(el, action)
    {

        if (el.data('confirm'))
        {
            if (!confirm(el.data('confirm')))
            {
                return;
            }
        }

        switch (action)
        {
            case 'crud_action':
                if (crud_actions[el.data('action')])
                {
                    crud_actions[el.data('action')](el);
                }
                else
                {
                    alert('Undefined action '+ el.data('action'));
                }
                break;

            case 'crud_popup':
                crud_actions.open_popup(el);
                break;

            case 'crud_event':
                //try to find table
                var params = {};
                var $table = el.parents('*[data-list_table_ref]').first();
                if ($table)
                {
                    params = $.extend({}, $table.data());
                    params.table = $table;

                }

                params = $.extend(params, el.data());
                crud.trigger(el.data('event'), params );
                break;
        }
    }

    $(function(){
        //crud.init_selects($('form'));
        //crud.init_date_pickers();
        crud.trigger("page.start");
        $("table[data-crud_table]").crud_list();
        $("div[data-crud_tree]").crud_tree();
        $("form[data-crud_form=ajax]").crud_form();
        $('[data-toggle="tooltip"]').tooltip({container: 'body'});
        $(crud.doc).on("shown.bs.tab", "a[data-toggle=tab]", function(e){
            if ($(e.target).data('id'))
            {
                crud.loc.hash = $(e.target).data('id');
            }
        });
    });



})(window, document, location, console, jQuery);
