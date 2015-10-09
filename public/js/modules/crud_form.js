;(function($, crud){


    var crud_actions = {
        open_form: function(elem)
        {
            crud.init_modal(elem.data("id"), elem.data("model"));
        }
    };

    crud.bind('page.start', function()
    {
        crud.add_actions(crud_actions);
        init_events();
    });



    function init_events()
    {

        crud.bind('crud.cancel_edit', function(data){

            //?? tab ??
            var id = 'tab_'+data.el.data('rel')
            if ($('div#'+id+'.tab-pane').length)
            {
                var cont = $('div#'+id);
                cont.parents('div[data-tabs_container]').first().find('.nav-tabs li:first a:first').click();
                var id = cont.attr('id');
                cont.remove();
                $('a[href=#'+id+']').parent().remove();

                $("html, body").animate({ scrollTop: 0 }, "slow");

            }

        });

        crud.bind('crud.edit_element', function(data){

            var table = data.el.parents('table[data-crud_table]').first();

            if (table.data('form_type') == 'tabs') {
                //open edit  tab
                crud.init_edit_tab(table, data.el.data('id'));
            } else {
                //init edit modal
                crud.init_modal(data.el.data('id'));
            }
        });

        //old stuff
        $('#crud_form').modal({backdrop:'static', 'keyboard':false, 'show':false})

        // REMOVE //
        $(crud.doc).on('change','.crud_checkbox', function () {

            var name = $(this).data('name');
            var hidden = $(this).parents('form').first().find('input[name='+name+']');
            if ($(this).prop('checked'))
            {
                hidden.val('1');
            } else
            {
                hidden.val('0');
            }
        });

        // END REMOVE //


        $(crud.doc).on('click', '.crud_submit', function (e)
        {
            e.preventDefault();
            var frm = $(this).parents("form:first");
            var attrs = ['close', 'reload'];
            for (var i =0; i<attrs.length; i++)
            {
                if ($(this).data(attrs[i]) != undefined)
                {
                    frm.data(attrs[i], $(this).data(attrs[i]));
                }
            }
            frm.submit();

        });

        // REMOVE //
        $(crud.doc).on('submit', 'form[data-crud_form=ajax]', function(e)
        {
            e.preventDefault();
            var $form = $(this);
            crud.toggle_editors_content($form);
            crud.toggle_form_progress($form);
            crud.init_form_progress($form);
            $form.ajaxSubmit({
                type: $form.attr('method'),
                url: $form.attr('action'),
                dataType: 'json',
                success: function(res){
                    crud.toggle_form_progress($form);
                    if (res.success)
                    {
                        if ($form.data('crud_model'))
                        {
                            if ($form.data('close'))
                            {
                                crud.trigger('crud.update', res);
                            }
                            else
                            {
                                crud.trigger("crud.reload", res);
                                crud.init_modal(res.crud_id, $form.data("crud_model"));
                            }
                            $('#crud_form form').trigger('reset');
                            crud.reset_selects();
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
                        alert(format_error(res.error));
                    }
                },
                error: function(res){
                    crud.toggle_form_progress($form);
                    alert(format_error(res.responseJSON.error.message))
                }
            });
        });

        // END REMOVE//

        // MOVE somewhere, toolkit? //

        $(crud.doc).on('click', '*[data-clone_fragment]', function (e) {
            e.preventDefault();
            clone_fragment($(this).data('clone_fragment'),$(this).data('clone_container'));

        });

        function clone_fragment(tpl_id, container_id)
        {
            var $tpl = $('#'+tpl_id).clone(true).attr('id','');
            var qtyAdded = $('#'+container_id).find('*[data-added]').length;

            $tpl.find('*[name]').each(function ()
            {
                $(this).attr('disabled', false);
                var name = $(this).attr('name');
                if (name.indexOf('[]')>0)
                {
                    var newName = name.replace('[]','')+'[-'+(qtyAdded+1)+']';
                    $(this).attr('name', newName)
                }
            });
            $tpl.attr('data-added',1);
            //calc order
            var ord  = $('#'+container_id).find('*[data-order]:visible').length;
            $tpl.find('*[data-order]').val((ord+1));


            $tpl.appendTo($('#'+container_id)).show();

        }

        function format_error(error)
        {
            if (error.indexOf("SQLSTATE[23000]")>=0)
            {
                return 'Невозможно сохранить: ДУБЛИКАТ';
            }
            else
            {
                return 'Произошла ошибка: ' + error;
            }

        }

        // END MOVE //



    }

})(jQuery, CRUD)