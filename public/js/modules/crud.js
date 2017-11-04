;(function(w, d, l, c, $){

    var listeners = {};
    var events = {};
    var settings = {
        model_edit_url: '/admin/crud/{model}/edit/{id}?scope={scope}',
        model_filter_url: '/admin/crud/{model}/filter/{scope}',
        model_list_url: '/admin/crud/{model}/list/{scope}{url_params}',
        model_delete_url: '/admin/crud/{model}/delete',
        model_move_tree_url: '/admin/crud/{model}/move_tree',
        model_tree_url: '/admin/crud/{model}/tree/{scope}',
        model_tree_options_url: '/admin/crud/{model}/tree_options?id={id}&field={field}',
        model_autocomplete_url: '/admin/crud/{model}/autocomplete',
        model_select_options_url: '/admin/crud/{model}/select_options',
        model_command_url: '/admin/crud/{model}/{id}/command/{command}?scope={scope}',
        model_validate_url: '/admin/crud/validate'

    };
    var crud_actions = {};
    var i18n = w.i18n;


    w.CRUD = w.CRUD || {
            win: w,
            doc: d,
            loc: l,
            con: c,
            //crudObj : w.crud_object_conf,
            bind: function(event, listener) {
                if (typeof(listeners[event]) == "undefined") {
                    listeners[event] = [];
                }
                listeners[event].push(listener);
                if (typeof(events[event]) != "undefined") {
                    listener(events[event]);
                }
            },
            trigger : function(event, data) {
                if (typeof(data) == "undefined") {
                    data = {};
                }
                if (typeof(listeners[event]) != "undefined") {
                    for (var i=0; i<listeners[event].length; i++) {
                        listeners[event][i](data);
                    }
                }
                events[event] = data;
            },
            format_setting: function(key, args) {
                return this.format_string(settings[key] || "", args);
            },
            format_string: function(str, args) {
                for (var i in args) {
                    str = str.replace('{' + i + '}', args[i] || "");
                }
                return str;
            },
            set_config: function(config) {
                for (var i in config) {
                    settings[i] = config[i];
                }
            },
            add_actions: function(actions) {
                for (var i in actions) {
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

            getActiveTab: function(elem) {
                if (elem && elem.parents("div.modal-dialog").length > 0) {
                    return null;
                }
                var c = $('div[data-tabs_container]');
                if (c.length == 1) {
                    var ul = $(".nav.nav-tabs:first", c);
                    var li = $("li.active", c);
                    if (li.length > 0) {
                        var tab_id = $("a[data-toggle]", li).attr('href');
                        var tab = $(".tab-pane"+tab_id, c);
                        return tab;
                    }
                }
            },

            getUrlParam: function(name, url, default_value) {
                if (!url) url = this.loc.href;
                name = name.replace(/[\[\]]/g, "\\$&");
                var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"), results = regex.exec(url);
                if (!results) return default_value;
                if (!results[2]) return default_value;
                return decodeURIComponent(results[2].replace(/\+/g, " "));
            },


            addModelParams: function(args, elem) {
                args['id'] = elem.data('id');
                args['model'] = elem.data('model') || "";
                args['scope'] = elem.data('scope') ? elem.data('scope') : "default";
                var context = elem.parents("[data-context-limiter]:first");
                if (context.length) {
                    if (!args['model'] && context.data('model')) {
                        args['model'] = context.data('model');
                    }
                    if (!args['id'] && context.data('id')) {
                        args['id'] = context.data('id');
                    }
                }

                var tbl = elem.parents("table[data-crud_table]:first");
                if (tbl.length) {
                    if (!args['model'] && tbl.data('crud_table')) {
                        args['model'] = tbl.data('crud_table');
                        if (tbl.data('crud_scope')) {
                            args['scope'] = tbl.data('crud_scope');
                        }
                    }
                    if (!args['id']) {
                        var row = elem.parents('tr:first');
                        if (row.length) {
                            if (row.data('id')) {
                                args['id'] = row.data('id');
                            } else {
                                var cell = $("td[data-id]:first", row);
                                if (cell.length && cell.data('id')) {
                                    args['id'] = cell.data('id');
                                }
                            }
                        }
                    }
                }
                var frm = elem.parents("form[data-crud_model]:first");
                if (frm.length) {
                    if (!args['model'] && frm.data('crud_model')) {
                        args['model'] = frm.data('crud_model');
                        if (frm.data('crud_scope')) {
                            args['scope'] = frm.data('crud_scope');
                        }
                    }
                    if (!args['id']) {
                        if (frm.data('crud_id')) {
                            args['id'] = frm.data('crud_id');
                        }
                    }
                }

                args['id'] = parseInt(args['id']);
                if (!args['id']) {
                    args['id'] = -1;
                }
                return args;

            },

            addContextVars: function(args, elem) {
                var context = elem.parents('[data-context-limiter]:first');
                var naming = 'name';
                if (context.length <= 0) {
                    context = elem.parents('form');
                } else {
                    naming = context.data('context-limiter');
                }
                this.trigger('form.before_validate', {form: context});
                $('input,select,textarea', context).each(function(){
                    var elm = $(this);
                    if (elm.is(':disabled')) {
                        return;
                    }
                    if (naming === 'container') {
                        args[elm.parents('[data-ref]:first').data('ref')] = elm.val();
                    } else {
                        if (elm.attr('name') && elm.attr('name').indexOf('[]') >= 0) {
                            var idx = elm.attr('name').replace('[]', '');
                            if (typeof(args[idx]) == 'undefined') {
                                args[idx] = [];
                            }
                            args[idx].push(elm.val());
                        } else {
                            args[elm.attr('name')] = elm.val();
                        }
                    }
                });

                return args;
            },

            getElemContext: function(elem)
            {
                var context = elem.parents('[data-context-limiter]:first');
                if (context.length <= 0)
                {
                    context = elem.parents("form");
                }
                return context;
            },

            init_edit_tab: function(model, id, args)
            {
                args = args || {};
                var scope = args.scope || "";
                var $tab_cont = args.table.parents('div.tabs-container').first();

                if ( $('a[href="#tab_'+model+'_'+scope+'_'+id+'"]',$tab_cont).length)
                {
                    $('a[href="#tab_'+model+'_'+scope+'_'+id+'"]',$tab_cont).first().click();
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

                var self = this;
                $.ajaxSetup({sync: false, context: this.doc.body});
                //$('#crud_form').load(url, function (res)
                var rargs = args.rargs?args.rargs:{};
                $.get(url, rargs, function(res)
                {
                    $('#crud_form').html(res);
                    //$('.modal-backdrop').remove();
                    $.ajaxSetup({async: true});
                    if (res == 'Access denied')
                    {
                        alert('Недостаточно прав доступа');
                        $('#crud_form').modal('hide');
                        return;
                    }
                    if (!$('#crud_form').hasClass('in')) {
                        //alert('show');
                        $('#crud_form').modal('show');
                        //alert('after_show');
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
                if (crud_actions[action]) {
                    return crud_actions[action](elm);
                }
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
            args = crud.addModelParams(args, elem);
            args = crud.addContextVars(args, elem);

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
                        var idx = 'callback_' + elem.data('callback');
                        if (typeof(crud_actions[idx]) != "undefined")
                        {
                            res['elem'] = elem;
                            crud_actions[idx](res);
                        }
                        else
                        {
                            eval(elem.data('callback'));
                        }
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
                    if ($("form", w).attr('id') != 'crud_filter_form')
                    {
                        $("form", w).crud_form();
                    }
                    if (crud_actions['onShow_' + popup]) {
                        crud_actions['onShow_' + popup]($('#' + popup));
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
