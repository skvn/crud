;(function($, crud, win){
    var i18n = win.i18n;
    bind_events();
    $.widget("crud.crud_form", {
        options: {},
        _create: function()
        {
            console.log('form created');
            
            var $form = this.element;

            //init controls
            crud.trigger('form.init', {form: $form});

            //submit
            $('input[type=submit],button[type=submit]', $form).on('click', function () {
                var frm = $(this).parents("form:first");
                prepare_form(frm, $(this));
            });


            $form.bootstrapValidator({
                live: 'enabled',
                trigger: null
            })
                .on('success.form.bv', function(e)
                {

                    e.preventDefault();
                    crud.trigger("form.before_submit", {form: $form});
                    //crud.toggle_editors_content($form);
                    //crud.toggle_form_progress($form);
                    //crud.init_form_progress($form);

                    $form.ajaxSubmit({
                        type: $form.attr('method'),
                        url: $form.attr('action'),
                        dataType: 'json',
                        success: function(res){
                            crud.trigger('form.after_submit', {form: $form})
                            //crud.toggle_form_progress($form);
                            if (res.success)
                            {

                                if ($form.data('crud_model'))
                                {
                                    var ref_scope = $form.data('crud_model')+'_'+$form.data('crud_scope');

                                    if ($form.data('close'))
                                    {
                                        crud.trigger("crud.reload", res);
                                        crud.trigger('crud.cancel_edit', {rel:$form.data('rel')});

                                    }
                                    else
                                    {

                                        crud.trigger("crud.reload", res);
                                        crud.trigger('crud.cancel_edit', {rel:$form.data('rel')});
                                        //alert(1);
                                        crud.trigger('crud.edit_element', { id: res.crud_id, ref: ref_scope});

                                    }
                                    $form.trigger('reset');
                                    //crud.reset_selects();
                                }
                                else
                                {
                                    if (res.message)
                                    {
                                        alert(res.message);
                                    }
                                    if ($form.data('callback_event'))
                                    {
                                        crud.trigger($form.data('callback_event'));
                                    }
                                    crud.trigger('crud.submitted', {form_id: $form.attr('id'), res: res, frm: $form});
                                }
                                if ($form.data("close"))
                                {
                                    $form.parents(".modal:first").modal('hide');
                                }
                                if ($form.data("reload"))
                                {
                                    crud.loc.reload();
                                }
                            }
                            else
                            {
                                alert(crud.format_error(res.error));
                            }
                        },
                        error: function(res){
                            crud.trigger('form.error_submit', {form: $form});
                            //crud.toggle_form_progress($form);
                            if (res.responseJSON && res.responseJSON.error && res.responseJSON.error.message) {
                                alert(res.responseJSON.error.message)
                            } else {
                                alert(i18n.say('error_sending_request'));
                            }
                        }
                    });
                }
                );



            //events
            //$('.crud_checkbox', $form).on('change', function () {
            //
            //    var name = $(this).data('name');
            //    var hidden = $form.find('input[name='+name+']');
            //    if ($(this).prop('checked'))
            //    {
            //        hidden.val('1');
            //    } else
            //    {
            //        hidden.val('0');
            //    }
            //});

            $('input[type=file]', $form).on('change', function (e) {

                var spl = $(this).val().split("\\");
                var name = spl[(spl.length-1)];
                var expl_name = name.split(".");
                name = expl_name[0];
                $("input[data-title_for='"+$(this).attr('name')+"']", $form).val(name);
            });

            $($form).on('change', 'select', function(e){
                crud.trigger('form.change', {form: $form, elem: $(this)});
            });


        },
        showFields: function(names)
        {
            for (var i in names)
            {
                //alert(names[i]+ ':' +$(".form-group[data-ref="+names[i]+"]", this.element).length);
                $(".form-group[data-ref="+names[i]+"]", this.element).show();
            }
        },
        hideFields: function(names)
        {
            for (var i in names)
            {
                $(".form-group[data-ref="+names[i]+"]", this.element).hide();
            }
        }
    });

    function bind_events()
    {
        var crud_actions = {
            open_form: function(elem)
            {
                crud.init_modal(elem.data("model"), elem.data("id"));
            },

            clone_fragment: function (elem)
            {

                var tpl_id = elem.data('fragment');
                var container_id = elem.data('container');
                var skip_arr = elem.data('skip_arr');
                var only_children = elem.data('only_children');

                if (only_children)
                {
                    var $tpl = $($('#'+tpl_id, crud.getActiveTab(elem)).html());
                } else {
                    var $tpl = $('#'+tpl_id, crud.getActiveTab(elem)).clone(true).attr('id','');
                }

                //console.log($tpl);

                var qtyAdded = $('#'+container_id, crud.getActiveTab(elem)).find('*[data-added]').length;
                $tpl =  $($('<div>').append($tpl).html().replace(new RegExp("(\\[NUM\\])", 'g'),qtyAdded+1));



                $tpl.find('*[name]').each(function ()
                {
                    $(this).attr('disabled', false);
                    var name = $(this).attr('name');
                    if (!skip_arr) {
                        if (name.indexOf('[]') > 0) {
                            var newName = name.replace('[]', '') + '[-' + (qtyAdded + 1) + ']';
                            $(this).attr('name', newName)
                        }
                    }
                });
                //$("*[data-widget]", $tpl).each(function(){
                //    var meth = $(this).data('widget');
                //    $(this)[meth]();
                //});
                $tpl.attr('data-added',1);
                //calc order
                var ord  = $('#'+container_id, crud.getActiveTab(elem)).find('*[data-order]:visible').length;
                $tpl.find('*[data-order]').val((ord+1));

                $tpl.appendTo($('#'+container_id, crud.getActiveTab(elem))).show();

            }
        };

        crud.add_actions(crud_actions);
        crud.bind('crud.cancel_edit', function(data){

            //?? tab ??
            var id = 'tab_'+data.rel;
            if ($('div#'+id+'.tab-pane').length)
            {
                var cont = $('div#'+id);
                cont.parents('div[data-tabs_container]').first().find('.nav-tabs li:first a:first').click();
                var id = cont.attr('id');
                cont.remove();
                $('a[href=#'+id+']').parent().remove();

                $("html, body").animate({ scrollTop: 0 }, "slow");

            } else {
                //alert('hide');
                $('form[data-rel='+data.rel+']').parents(".modal:first").modal('hide');
            }

        });



        crud.bind('crud.edit_element', function(data){

            if (data.ref)
            {

                data.table = $('*[data-list_table_ref='+data.ref+']');
            }


            var model = data.table.data('crud_table')?data.table.data('crud_table'):data.table.data('crud_tree');
            if (data.table && data.table.data('form_type') == 'tabs') {
                //open edit  tab
                crud.init_edit_tab(model, data.id, {table: data.table, scope: data.table.data('crud_scope'), rargs:data.rargs?data.rargs:{}});
            } else {
                //init edit modal
                crud.init_modal(model, data.id, {scope: data.table.data('crud_scope'), rargs:data.rargs?data.rargs:{}});
            }
        });

        $(crud.doc).on('click', '.crud_submit', function (e)
        {
            e.preventDefault();
            var frm = $(this).parents("form:first");
            prepare_form(frm, $(this));
            frm.submit();

        });

        crud.bind("form.before_submit", function(data){
            toggle_progress(data['form']);
            init_progress(data['form']);
        });
        crud.bind("form.after_submit", function(data){
            toggle_progress(data['form']);
        });
        crud.bind("form.error_submit", function(data){
            toggle_progress(data['form']);
        });
        crud.bind("form.init", function(data){
            $("*[data-widget]", data['form']).each(function () {

                var wname = $(this).data('widget');
                $(this)[wname]();
            });
        });
    }



    function prepare_form(frm, elem)
    {
        var attrs = ['close', 'reload'];
        for (var i =0; i<attrs.length; i++)
        {
            if (elem.data(attrs[i]) != undefined)
            {
                frm.data(attrs[i], elem.data(attrs[i]));
            }
        }

    }

    function toggle_progress(elem)
    {
        $('.modal-footer button, .modal-footer .progress', elem).toggleClass('hide');
        $('.modal-footer button', elem).each(function () {

            if (!$(this).hasClass('hide'))
            {
                $(this).removeAttr('disabled');
            }
        });

    }

    function init_progress(elem)
    {
        var bar = $('.modal-footer .progress .progress-bar', elem);
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
    }





})(jQuery, CRUD, window)