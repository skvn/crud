;(function($, crud){
    $.widget("crud.crud_form", {
        options: {},
        _create: function()
        {
            
            var $form = this.element;

            //init controls
            crud.init_selects($form);
            crud.init_date_pickers($form);
            crud.init_html_editors($form);
            crud.init_ichecks($form);
            $('input[type=text]:first', $form).focus();

            //submit
            $('input[type=submit],button[type=submit]', $form).on('click', function () {


                var frm = $(this).parents("form:first");
                var attrs = ['close', 'reload'];
                for (var i =0; i<attrs.length; i++)
                {
                    if ($(this).data(attrs[i]) != undefined)
                    {
                        frm.data(attrs[i], $(this).data(attrs[i]));
                    }
                }


            });
            $form.on('submit', function(e)
            {

                e.preventDefault();
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

                                }
                                $form.trigger('reset');
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
                        alert(res.responseJSON.error.message)
                    }
                });
            });

            //events
            $('.crud_checkbox', $form).on('change', function () {

                var name = $(this).data('name');
                var hidden = $form.find('input[name='+name+']');
                if ($(this).prop('checked'))
                {
                    hidden.val('1');
                } else
                {
                    hidden.val('0');
                }
            });


        }
    });



})(jQuery, CRUD)