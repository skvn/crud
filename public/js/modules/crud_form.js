;(function($, crud){


    var close = false;
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

    //$(crud.doc).ready(function ()
    //{
    //    init_events();
    //});



    function init_events()
    {
        $('#crud_form').modal({backdrop:'static', 'keyboard':false, 'show':false})

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
        crud.bind('crud.update', function(res){
            // console.log(res);
            if (res.success)
            {

                $('#crud_form form').trigger('reset');
                crud.reset_selects();
                $('#crud_form').modal('hide');


            }
        });
        //$(crud.doc).on('crud.update', function(ev,res)
        //{
        //    // console.log(res);
        //    if (res.success)
        //    {
        //
        //        $('#crud_form form').trigger('reset');
        //        crud.reset_selects();
        //        $('#crud_form').modal('hide');
        //
        //
        //    }
        //});
        crud.bind('ajax_form.return', function(data){
            if(data.res['success'])
            {
                if (data.frm.data('close'))
                {
                    data.frm.parents(".modal:first").modal('hide');
                }
                if (data.frm.data("reload"))
                {
                    crud.loc.reload();
                }
            }
            else
            {
                alert(data.res['error']);
            }
        });
        //$(crud.doc).on('ajax_form.return', function(ev, data){
        //    if(data.res['success'])
        //    {
        //        if (data.frm.data('close'))
        //        {
        //            data.frm.parents(".modal:first").modal('hide');
        //        }
        //        if (data.frm.data("reload"))
        //        {
        //            crud.loc.reload();
        //        }
        //    }
        //    else
        //    {
        //        alert(data.res['error']);
        //    }
        //});

        $(crud.doc).on('submit', '#crud_filter_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            crud.toggle_form_progress($form);
            crud.init_form_progress($form);
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.toggle_form_progress($form)
                        crud.trigger('crud.filter_set',res);
                        //$(crud.doc).trigger('crud.filter_set',res);

                    }

                }
            );

        });

        $(crud.doc).on('reset', '#crud_filter_form', function (e) {
            //e.preventDefault();
            var $form = $(this);
            $('select', $form).each(function (){
               $(this).select2("val", null);
            });

            $('input', $form).each(function (){
                $(this).val('');
            });


            crud.toggle_form_progress($form);
            crud.init_form_progress($form);
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.toggle_form_progress($form)
                        crud.trigger('crud.filter_set',res);
                        //$(crud.doc).trigger('crud.filter_set',res);
                    }

                }
            );

        });

        $(crud.doc).on('click', '.crud_submit', function (e) {
            e.preventDefault();
            close = parseInt($(this).data('close'))
            $(this).parents('form').find('input[type=submit]').click();

        });


        $(crud.doc).on('submit', '*[data-crud_role=form_container] form', function (e) {
            e.preventDefault();
            var $form = $(this);
            crud.toggle_editors_content($form);
            crud.toggle_form_progress($form);
            crud.init_form_progress($form);

            $(this).ajaxSubmit(
                {
                    type:'POST',
                    //url: '/admin/crud/'+CRUD.crudObj['class_name']+'/update/'+$(this).parent().data('crud_id'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    success: function (res) {
                        crud.toggle_form_progress($form)

                        if (res.success) {

                            if (close > 0) {
                                //$(crud.doc).trigger('crud.update', res);
                                crud.trigger('crud.update', res);

                            } else {
                                var id = res.crud_id
                                crud.trigger('crud.reload', res);
                                //$(crud.doc).trigger('crud.reload', res);
                                crud.init_modal(id, $form.parent().data('crud_model'));
                            }

                        } else {

                            if (res.error.indexOf("SQLSTATE[23000]")>=0)
                            {
                                alert('Невозможно сохранить: ДУБЛИКАТ');

                            } else {
                                alert('Произошла ошибка: ' + res.error);
                            }
                        }

                    },
                    error: function(data){
                        //console.log(data);
                        crud.toggle_form_progress($form);
                        if (data.responseJSON.error.code == 23000)
                        {
                            alert('Невозможно сохранить: ДУБЛИКАТ');

                        } else {
                            alert('Произошла ошибка: ' + data.responseJSON.error.message);
                        }
                    }


                }
            );

        });


        $(crud.doc).on('submit', 'form.ajax_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            crud.toggle_editors_content($form);
            crud.toggle_form_progress($form);
            crud.init_form_progress($form);

            $(this).ajaxSubmit(
                {
                    type:$form.attr('method'),
                    url: $form.attr('action'),
                    dataType: 'json',
                    success: function (res) {


                        crud.toggle_form_progress($form)
                        if (res.success) {
                            //$(crud.doc).trigger('ajax_form.return', {form_id: $form.attr('id'), res: res, frm: $form});
                            crud.trigger('ajax_form.return', {form_id: $form.attr('id'), res: res, frm: $form});
                        } else {
                            alert('Произошла ошибка: ' + res.error);
                        }

                    },
                    error: function(data){

                        crud.toggle_form_progress($form);
                        alert('Произошла ошибка: ' + data.responseJSON.error.message);

                    }

                }
            );

        });

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




    }

})(jQuery, CRUD)