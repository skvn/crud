;(function($, crud){


    crud.bind('page.start', function()
    {
        init_events();
    });



    function init_events()
    {


        $(crud.doc).on('submit', '#crud_filter_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            crud.toggle_form_progress($form);
            crud.init_form_progress($form);
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: crud.format_setting("model_filter_url", {model: crud.crudObj['class_name'], scope: $(this).data('crud_scope')}),
                    //url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.toggle_form_progress($form)
                        crud.trigger('crud.filter_set', res);

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
                    url: crud.format_setting('model_filter_url', {model: crud.crudObj['class_name'], scope: $(this).data('crud_scope')}),
                    //url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.toggle_form_progress($form)
                        crud.trigger('crud.filter_set',res);
                    }

                }
            );

        });

     }

})(jQuery, CRUD)