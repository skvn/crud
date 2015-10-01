;(function(w, d, l, c, $){
    var listeners = {};
    var events = {};
    var settings = {
        model_edit_url: '/admin/crud/{model}/edit/{id}',
        model_filter_url: '/admin/crud/{model}/filter/{scope}',
        model_list_url: '/admin/crud/{model}/list/{scope}',
        model_delete_url: '/admin/crud/{model}/delete',
        model_move_tree_url: '/admin/crud/{model}/move_tree',
        model_tree_url: '/admin/crud/tree/{model}'
    };
    var crud_actions = {};


    w.CRUD = w.CRUD || {
        win: w,
        doc: d,
        loc: l,
        con: c,
        crudObj : w.crud_object_conf,
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
                str = str.replace('{' + i + '}', args[i]);
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
        init_modal: function(id, class_name)
        {
            var model = class_name || this.crudObj['class_name'];
            //var url = '/admin/crud/'+model+'/edit/'+id;
            var url = this.format_setting("model_edit_url", {model: model, id: id});
            $('#crud_form').html('');
            $('#crud_form').modal('show');
            var self = this;
            $('#crud_form').load(url, function (res)
            {
                if (res == 'Access denied')
                {
                    alert('Недостаточно прав доступа');
                    $('#crud_form').modal('hide');
                    return;
                }
                self.init_selects($('#crud_form'));
                self.init_date_pickers();
                self.init_html_editors();
                self.init_ichecks($('#crud_form'));
                $('#crud_form input[type=text]:first').focus();
                self.trigger('crud.content_loaded', {cont: $('#crud_form')});
                //$(self.doc).trigger("crud.content_loaded", {cont: $('#crud_form')});
            });
        },
        toggle_form_progress: function($form)
        {
            $form.find('.modal-footer button, .modal-footer .progress').toggleClass('hide');
        },
        init_date_pickers: function()
        {
            $('.input-group.date').datepicker({
                todayBtn: "linked",
                keyboardNavigation: true,
                forceParse: true,
                calendarWeeks: true,
                autoclose: true,
                weekStart:1,
                language: 'ru'
            });
        },
        init_selects: function($form)
        {
            function format(op) {

                var text = op.text;
                if ($(op.element).data('group'))
                {
                    text += ' (<em>'+$(op.element).data('group')+'</em>)';
                }
                return text;

            }

            $form.each(function (){

                $('select',$(this)).select2(
                    {
                        'allowClear': true,
                        formatSelection: format
                    });

            });
        },
        reset_selects: function()
        {
            $('#crud_form select').select2('val','');
        },
        init_form_progress: function($form)
        {
            var bar = $form.find('.modal-footer .progress .progress-bar');
            this.start_progress_bar(bar);
        },
        start_progress_bar: function(bar)
        {
            bar.parent().removeClass('hide');
            var current_perc = 0;
            bar.css('width', (current_perc)+'%');
            var perc = 99;
            var progress = setInterval(function() {
                if (current_perc>=perc) {
                    clearInterval(progress);
                } else {
                    current_perc +=1;
                    bar.css('width', (current_perc)+'%');
                }

            }, 20);
        },
        stop_progress_bar: function(bar)
        {
            bar.parent().addClass('hide');
        },
        init_ichecks: function(cont)
        {
            $('.i-checks', cont).iCheck({
                checkboxClass: 'icheckbox_square-green',
                radioClass: 'iradio_square-green'
            }).on('ifChanged', function ()
            {

                var name = $(this).data('name');
                if (name) {
                    var hidden = $(this).parents('form').first().find('input[name=' + name + ']');
                    if (hidden.length) {
                        if ($(this).prop('checked')) {
                            hidden.val('1');
                        } else {
                            hidden.val('0');
                        }
                    }
                }
                var disabled = true;
                $(this).parents('table').find('tr td input.i-checks').each(function () {
                    if ($(this).prop('checked'))
                    {
                        $(this).parents('tr').addClass('success');
                        disabled = false;
                        return;
                    } else
                    {
                        $(this).parents('tr').removeClass('success');
                    }
                });
                $('.crud_delete').attr('disabled', disabled);
                $('.crud_table_command').attr('disabled', disabled);
            })
        },
        init_html_editors: function()
        {
            $('.html_editor').summernote({height: 500, linksArray: this.win.crudAttachOptions});
        },
        toggle_editors_content: function($form)
        {
            $form.find('textarea.html_editor').each(function () {

                $(this).val($(this).code());
            })
        }
    };
    w.onerror = function(msg, file, line)
    {
        if (msg == 'Script error.' && !file)
        {
            return false;
        }
        new Image().src = "/util/log_js?msg=" + encodeURIComponent(msg) + "&file=" + encodeURIComponent(file) + "&line=" + encodeURIComponent(line) + "&uri=" + encodeURIComponent(l.href) + "&ref=" + encodeURIComponent(d.referrer);
        if (!msg)
        {
            msg = "";
        }
        w.onerror = null;
        return false;
    }
    var crud = w.CRUD;
    crud_actions = {

        open_popup: function(elem)
        {
            var popup = elem.data('popup');
            if ($('#'+popup).length <= 0)
            {
                $('<div id="'+popup+'" style="display: none;"></div>').appendTo($(crud.doc.body));
            }
            $.get(elem.data('uri'), $.extend({}, elem.data()), function(res){
                $('#'+popup).replaceWith(res);
                crud.trigger('crud.content_loaded', {cont: $('#'+popup)});
                //$(document).trigger("crud.content_loaded", {cont: $('#'+popup)});
                $('#'+popup).modal('show');
                crud.init_ichecks($('#'+popup));
            });
        },
        call_uri: function(elem)
        {
            $.get(elem.data('uri'), $.extend({}, elem.data()), function(res){
                alert(res['message']);
            });
        },
        crud_command: function(elem)
        {
            var args = elem.data('args');
            $.post(elem.attr('href'), args, function (res)
            {
                if (res.success)
                {
                    if (res.message)
                    {
                        alert(res.message);
                    }
                    if (elem.data('callback_event'))
                    {
                        crud.trigger(elem.data('callback_event'), res);
                        //$(crud.doc).trigger($self.data('callback_event'), res);
                    }
                    else if (elem.data('callback'))
                    {
                        eval(elem.data('callback'));
                    }
                }
                else
                {
                    alert(res.error);
                }

            }, 'json')
        }
    };
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(crud.doc).on('click','*[data-click]', function (e){
            e.preventDefault();
            if ($(this).data('confirm'))
            {
                if (!confirm($(this).data('confirm')))
                {
                    return;
                }
            }

            switch ($(this).data('click'))
            {
                case 'crud_action':
                    if (crud_actions[$(this).data('action')])
                    {
                        crud_actions[$(this).data('action')]($(this));
                    }
                    else
                    {
                        alert('Undefined action '+ $(this).data('action'));
                    }
                    break;
            }

        }
    );
    //crud.bind('crud.update', function(res)
    //{
    //    if (res.success == false)
    //    {
    //        alert(res.error);
    //    }
    //});

    $(function(){
        crud.init_selects($('form'));
        crud.init_date_pickers();
        crud.trigger("page.start");
        $("table[data-crud_table]").crud_list();
    });
})(window, document, location, console, jQuery);
