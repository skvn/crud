;(function(w, d, l, c, $){

    var listeners = {};
    var events = {};
    var settings = {
        model_edit_url: '/admin/crud/{model}/edit/{id}?scope={scope}',
        model_filter_url: '/admin/crud/{model}/filter/{scope}',
        model_list_url: '/admin/crud/{model}/list/{scope}',
        model_delete_url: '/admin/crud/{model}/delete',
        model_move_tree_url: '/admin/crud/{model}/move_tree',
        model_tree_url: '/admin/crud/{model}/tree/{scope}',
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
            cleanPastedHTML:  function (input) {
                // 1. remove line breaks / Mso classes
                var stringStripper = /(\n|\r| class=(")?Mso[a-zA-Z]+(")?)/g;
                var output = input.replace(stringStripper, ' ');
                // 2. strip Word generated HTML comments
                var commentSripper = new RegExp('<!--(.*?)-->','g');
                var output = output.replace(commentSripper, '');
                var tagStripper = new RegExp('<(/)*(meta|link|span|\\?xml:|st1:|o:|font)(.*?)>','gi');
                // 3. remove tags leave content if any
                output = output.replace(tagStripper, '');
                // 4. Remove everything in between and including tags '<style(.)style(.)>'
                var badTags = ['style', 'script','applet','embed','noframes','noscript'];

                for (var i=0; i< badTags.length; i++) {
                    tagStripper = new RegExp('<'+badTags[i]+'.*?'+badTags[i]+'(.*?)>', 'gi');
                    output = output.replace(tagStripper, '');
                }
                // 5. remove attributes ' style="..."'
                var badAttributes = ['style', 'start'];
                for (var i=0; i< badAttributes.length; i++) {
                    var attributeStripper = new RegExp(' ' + badAttributes[i] + '="(.*?)"','gi');
                    output = output.replace(attributeStripper, '');
                }
                return output;
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
                $tpl_tab.find('a').html($tpl_tab.find('a').html().replace('[ID]','['+id_label+']').replace('[REL]', rel));
                $tpl_tab.appendTo($tab_cont.find('ul.nav-tabs').first())
                    .show()
                    .find('a').first()
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
                    $cont.find('form').first().crud_form();
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

                    $('#crud_form').find('form').first().crud_form();
                    self.trigger('crud.content_loaded', {cont: $('#crud_form')});

                });


            },
            toggle_form_progress: function($form)
            {
                $form.find('.modal-footer button, .modal-footer .progress').toggleClass('hide');
            },
            init_date_pickers: function(container)
            {
                //date
                if (container)
                {
                    var $coll = $('.input-group.date', container);
                } else {
                    var $coll = $('.input-group.date');
                }
                $coll.datepicker(
                    {
                        todayBtn: "linked",
                        keyboardNavigation: true,
                        forceParse: true,
                        calendarWeeks: true,
                        autoclose: true,
                        weekStart:1,
                        language: 'ru'
                }).on('changeDate', function(e) {
                    // Revalidate the date field

                    $(this).parents('form').first().bootstrapValidator('revalidateField',$(this).find('input'));
                });
                //datetime
                if (container)
                {
                    var $coll = $('.input-group.date_time', container);
                } else {
                    var $coll = $('.input-group.date_time');
                }
                $coll.each(function () {
                    $(this).val(moment($(this).val));
                    $(this).datetimepicker(
                        {
                            locale: "ru",
                            format: $(this).data('format')

                        }).on('dp.change', function(e) {
                        // Revalidate the date field
                        $(this).parents('form').first().bootstrapValidator('revalidateField',$(this).find('input'));
                    });
                })

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

                     $('select:not(.default_select)',$(this)).selectpicker({
                         liveSearch: true

                     });
                    //$('select:not(.default_select)',$(this)).select2(
                    //    {
                    //        allowClear: true,
                    //        formatSelection: format
                    //    });

                });
            },

            init_tag_inputs: function ($form)
            {
                var self = this;
                $form.each(function (){

                    var self_form = $(this);
                    $('.tagsinput',self_form).each(function () {

                        var scope = self_form.data('crud_scope');
                        var model = $(this).data('model');
                        var url = self.format_setting("model_autocomplete_url", {model: model, scope:scope});
                        var fid = $(this).attr('id');

                        var options = new Bloodhound({
                            datumTokenizer: function (datum) {
                                return Bloodhound.tokenizers.whitespace(datum.name);
                            },
                            queryTokenizer: Bloodhound.tokenizers.whitespace,
                            limit: 5,
                            remote: {
                                url: url,
                                replace: function(url, query) {
                                    return url + "?q=" + query;
                                }
                            }
                        });

                        options.initialize();
                        var tagApi = $(this).tagsManager({
                            hiddenTagListId: fid+'_hidden',
                            deleteTagsOnBackspace: false,
                            prefilled: $('#'+fid+'_hidden').val()
                        });
                        $('#'+fid).typeahead(null, {
                            minLength: 1, // send AJAX request only after user type in at least X characters
                            source: options.ttAdapter()
                        }).on('typeahead:selected', function (e, d) {
                            tagApi.tagsManager("pushTag", d);
                        });


                        //$(this).tagsinput(
                        //    {
                        //        typeahead: {
                        //            //source: function (query)
                        //            //{
                        //            //    alert(query);
                        //            //    return $.getJSON(url+'?q='+query);
                        //            //}
                        //            source: options.ttAdapter()
                        //    }
                        //});
                    });

                });
            },

            reset_selects: function()
            {
                //$('#crud_form select').select2('val','');
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
                    checkboxClass: 'icheckbox_square',
                    radioClass: 'iradio_square',
                    increaseArea: '20%'
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
            init_html_editors: function(container)
            {
                if (container)
                {
                    $coll_sum = $('.html_editor[data-editor_type="summernote"]', container);
                    $coll_tmce = $('.html_editor[data-editor_type="tinymce"]', container);
                    $coll_mde = $('.html_editor[data-editor_type="mde"]', container);

                } else {
                    $coll_sum = $('.html_editor[data-editor_type="summernote"]');
                    $coll_tmce = $('.html_editor[data-editor_type="tinymce"]');
                    $coll_mde = $('.html_editor[data-editor_type="mde"]');
                }

                if ($coll_mde.length) {

                    simple_mdes = new Object();
                    $coll_mde.each(function () {
                        simple_mdes[this.id] = new SimpleMDE(
                            {
                                element: this,
                                toolbar: ["heading","bold", "italic", "strikethrough", "|", "code","quote","|","unordered-list","ordered-list","|","link","image","table","|","clean-block","|","preview","guide"],
                                spellChecker:false


                            }
                        );
                    });

                }
                //summernote
                if ($coll_sum.length) {
                    $coll_sum.summernote({
                        //FIXME
                        lang: 'ru-RU',
                        toolbar: [

                            ['style', ['style', 'bold', 'italic', 'underline', 'clear']],
                            ['font', ['strikethrough', 'superscript', 'subscript']],
                            ['fontsize', ['fontsize']],
                            ['color', ['color']],
                            ['para', ['ul', 'ol', 'paragraph']],
                            ['insert', ['picture', 'link', 'video', 'table']]

                        ],
                        onpaste: function (e) {
                            var thisNote = $(this);
                            var updatePastedText = function (someNote) {
                                var original = someNote.code();
                                var cleaned = crud.cleanPastedHTML(original); //this is where to call whatever clean function you want. I have mine in a different file, called CleanPastedHTML.
                                someNote.code('').html(cleaned); //this sets the displayed content editor to the cleaned pasted code.
                            };
                            setTimeout(function () {
                                //this kinda sucks, but if you don't do a setTimeout,
                                //the function is called before the text is really pasted.
                                updatePastedText(thisNote);
                            }, 10);


                        },
                        height: 500, linksArray: this.win.crudAttachOptions
                    });
                }

                if ($coll_tmce.length)
                {
                    $coll_tmce.each(function () {

                        if (container)
                        {
                            container.append($('<input type="hidden" name="_attach_to_link_" id="_attach_to_link_" />'))
                        }
                        tinymce.init({
                            selector: '#'+$(this).attr('id'),
                            height: 430,
                            toolbar: "paste  | undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media | code",
                            plugins: ["image", "link", "media", "code"] ,
                            paste_as_text: true,
                            automatic_uploads: false,
                            relative_urls : false,
                            remove_script_host : false,
                            convert_urls : true,
                            image_advtab: true,

                            file_picker_callback: function(callback, value, meta) {
                                if (!$('#tiny_file').length)
                                {
                                    $('<input type="file" class="input" name="tiny_file" id="tiny_file" style="display: none"/>').appendTo('body');

                                    $('#tiny_file').on('change', function (event) {

                                        $('.mce-widget.mce-btn.mce-primary.mce-first').css({left:'350px', width:'100px'}).find('button span').text('Loading...');
                                        var formData = new FormData();
                                        formData.append("file", this.files[0]);
                                        $.ajax({
                                            url : '/admin/crud/attach_upload',
                                            type : 'POST',
                                            data : formData,
                                            processData: false,
                                            contentType: false,
                                            success : function(data) {
                                                $('.mce-widget.mce-btn.mce-primary.mce-first').css({left:'411px', width:'50px'}).find('button span').text('Ok');

                                                $('#_attach_to_link_').val($('#_attach_to_link_').val() + ' '+data.id);
                                                if (meta.filetype == 'file') {
                                                    callback(data.url);
                                                }

                                                // Provide image and alt text for the image dialog
                                                if (meta.filetype == 'image') {
                                                    callback(data.url, {width:data.width, height:data.height});
                                                }
                                            }
                                        });
                                    });

                                }
                                $('#tiny_file').trigger('click');




                            },
                            //FIXME
                            language: i18n.say('locale_short')
                        });

                    })
                }

            },
            toggle_editors_content: function($form)
            {

                $form.find('textarea[data-editor_type=mde]').each(function () {
                    if (simple_mdes[this.id])
                    {
                        $(this).val(simple_mdes[this.id].value())
                    }
                });


                $form.find('textarea[data-editor_type=summernote]').each(function () {

                    $(this).val($(this).summernote('code'));
                })
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
                return  crud_actions[action](elm);;
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
            if (elem.data('collect_rows'))
            {
                var tbl = $('table[data-list_table_ref='+elem.data('collect_rows')+']');
                if (tbl.length <= 0)
                {
                    alert('Таблица данных не найдена');
                    return;
                }
                var ids = [];
                $('input[data-rel=row]', tbl).each(function(){
                    if ($(this).prop('checked'))
                    {
                        ids.push($(this).val());
                    }
                })
                if (ids.length <= 0)
                {
                    alert(i18n.say('no_item_selected'));
                    return;
                }
                args['ids'] = ids;
            }
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

            }, 'json').fail(function (res) {
                alert(res.statusText)
            });

        },
        open_popup: function(elem)
        {
            var popup = elem.data('popup');
            if ($('#'+popup).length <= 0)
            {
                $('<div id="'+popup+'" style="display: none;"></div>').appendTo($(crud.doc.body));
            }
            $.get(elem.data('uri'), $.extend({}, elem.data()), function(res){
                $('#'+popup).replaceWith(res);
                var w = $('#'+popup);
                crud.trigger('crud.content_loaded', {cont: w});
                w.modal({keyboard:false, show:true,backdrop:'static'});
                if (elem.data('title'))
                {
                    $("h4", w).html(elem.data("title"));
                }
                if (elem.data('report'))
                {
                    $("input[name=type]", w).val(elem.data('report'))
                }
                if (elem.data('source'))
                {
                    $("input[name=source]", w).val(elem.data('source'))
                }
                if (crud_actions['onShow_'+popup])
                {
                    crud_actions['onShow_'+popup]($('#'+popup));
                }
                if (elem.data("onshow"))
                {
                    switch (elem.data("onshow"))
                    {
                        case 'pass_row_ids':
                            var ids = [];
                            $('table input[data-rel=row]').each(function ()
                            {
                                if ($(this).prop('checked'))
                                {
                                    ids.push($(this).attr('value'));
                                }
                            });
                            $('input[name=row_ids]', w).val(ids.join(','));
                            break;
                    }
                }
                crud.init_ichecks(w);
                $("form", w).crud_form();
            });
        }

    };
    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
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
        crud.init_selects($('form'));
        crud.init_date_pickers();
        crud.trigger("page.start");
        $("table[data-crud_table]").crud_list();
        $("div[data-crud_tree]").crud_tree();
        $("form[data-crud_form=ajax]").crud_form();
    });



})(window, document, location, console, jQuery);
