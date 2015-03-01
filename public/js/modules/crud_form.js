;(function($, window, CRUD){


    var close = false;

    $(document).ready(function ()
    {
        init_events();
    });



    function init_events()
    {
        $('#crud_form').modal({backdrop:'static', 'keyboard':false, 'show':false})

        $(document).on('change','.crud_checkbox', function () {

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
        $(document).on('crud.update', function(ev,res)
        {
            // console.log(res);
            if (res.success)
            {

                $('#crud_form form').trigger('reset');
                CRUD.reset_selects();
                $('#crud_form').modal('hide');


            }
        });
        $(document).on('ajax_form.return', function(ev, data){
            if(data.res['success'])
            {
                if (data.frm.data('close'))
                {
                    data.frm.parents(".modal:first").modal('hide');
                }
                if (data.frm.data("reload"))
                {
                    window.location.reload();
                }
            }
            else
            {
                alert(data.res['error']);
            }
        });

        $(document).on('submit', '#crud_filter_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            CRUD.toggle_form_progress($form);
            CRUD.init_form_progress($form);
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: '/admin/crud/'+CRUD.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        CRUD.toggle_form_progress($form)
                        $(document).trigger('crud.filter_set',res);

                    }

                }
            );

        });

        $(document).on('reset', '#crud_filter_form', function (e) {
            //e.preventDefault();
            var $form = $(this);
            $('select', $form).each(function (){
               $(this).select2("val", null);
            });

            $('input', $form).each(function (){
                $(this).val('');
            });


            CRUD.toggle_form_progress($form);
            CRUD.init_form_progress($form);
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: '/admin/crud/'+CRUD.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        CRUD.toggle_form_progress($form)
                        $(document).trigger('crud.filter_set',res);
                    }

                }
            );

        });

        $(document).on('click', '.crud_submit', function (e) {
            e.preventDefault();
            close = parseInt($(this).data('close'))
            $(this).parents('form').find('input[type=submit]').click();

        });


        $(document).on('submit', '*[data-crud_role=form_container] form', function (e) {
            e.preventDefault();
            var $form = $(this);
            CRUD.toggle_editors_content($form);
            CRUD.toggle_form_progress($form);
            CRUD.init_form_progress($form);

            $(this).ajaxSubmit(
                {
                    type:'POST',
                    //url: '/admin/crud/'+CRUD.crudObj['class_name']+'/update/'+$(this).parent().data('crud_id'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    success: function (res) {
                        CRUD.toggle_form_progress($form)

                        if (close>0) {
                            $(document).trigger('crud.update', res);

                        } else
                        {
                            var id = res.crud_id
                            $(document).trigger('crud.reload', res);
                            CRUD.init_modal(id, $form.parent().data('crud_model'));
                        }

                    }

                }
            );

        });


        $(document).on('submit', 'form.ajax_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            CRUD.toggle_editors_content($form);
            CRUD.toggle_form_progress($form);
            CRUD.init_form_progress($form);

            $(this).ajaxSubmit(
                {
                    type:$form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    success: function (res) {

                        CRUD.toggle_form_progress($form)
                        $(document).trigger('ajax_form.return',{form_id:$form.attr('id'), res:res, frm: $form});

                    }

                }
            );

        });

        $(document).on('click', '*[data-clone_fragment]', function (e) {
            e.preventDefault();
            clone_fragment($(this).data('clone_fragment'),$(this).data('clone_container'));

        });

        function clone_fragment(tpl_id, container_id)
        {
            $tpl = $('#'+tpl_id).clone(true).attr('id','');
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




    }

})(jQuery, window, CRUD)