;(function ($, crud){
    $.widget("crud.crud_checkbox", {
        options: {},
        _create: function(){
            this.element.iCheck({
                checkboxClass: 'icheckbox_square',
                radioClass: 'iradio_square',
                increaseArea: '20%'
            }).on('ifChanged', function ()
            {
                //var name = $(this).data('name');
                //if (name) {
                    //var hidden = $(this).parents('form').first().find('input[name=' + name + ']');
                    //console.log($(this).parent().parent());
                    var hidden = $('input[type=hidden]', $(this).parent().parent());
                    if (hidden.length) {
                        if ($(this).prop('checked')) {
                            hidden.val('1');
                        } else {
                            hidden.val('0');
                        }
                        hidden.trigger('change');
                    }
                //}
                var disabled = true;
                $(this).parents('table').find('tr td input.i-checks').each(function () {
                    if ($(this).prop('checked'))
                    {
                        $(this).parents('tr').addClass('success').addClass('selected');
                        disabled = false;
                        return;
                    } else
                    {
                        $(this).parents('tr').removeClass('success').removeClass('selected');
                    }
                });
                $('.crud_delete').attr('disabled', disabled);
                $('.crud_table_command').attr('disabled', disabled);
            })
        }
    });
})(jQuery, CRUD)