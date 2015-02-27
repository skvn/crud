function CRUD(crudObj) {

    this.crudObj = crudObj;

    this.init_modal = function (id, class_name)
    {
        var model = class_name || this.crudObj['class_name'];
        var url = '/admin/crud/'+model+'/edit/'+id;
        $('#crud_form').html('');
        $('#crud_form').modal('show');
        $('#crud_form').load(url, function (res)
        {
            if (res == 'Access denied')
            {
                alert('Недостаточно прав доступа');
                $('#crud_form').modal('hide');
                return;
            }
            CRUD.init_selects($('#crud_form'));
            CRUD.init_date_pickers();
            CRUD.init_html_editors();
            CRUD.init_ichecks($('#crud_form'));
            $('#crud_form input[type=text]:first').focus();
            $(document).trigger("crud.content_loaded", {cont: $('#crud_form')});
        });
    };


    this.toggle_form_progress = function ($form)
    {
        $form.find('.modal-footer button, .modal-footer .progress').toggleClass('hide');
    };


    this.init_date_pickers = function ()
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


    };

    this.init_selects = function ($form)
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


    };

    this.reset_selects = function ()
    {
        $('#crud_form select').select2('val','');
    };

    this.init_form_progress = function($form)
    {
        var bar = $form.find('.modal-footer .progress .progress-bar');
        this.start_progress_bar(bar);

    };

    this.start_progress_bar = function(bar)
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
    };

    this.stop_progress_bar = function(bar)
    {
        bar.parent().addClass('hide');
    };




    this.init_ichecks = function(cont)
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
    };

    this.init_html_editors = function ()
    {

        $('.html_editor').summernote({height: 500, linksArray:window.crudAttachOptions});

    };

    this.toggle_editors_content = function ($form)
    {
        $form.find('textarea.html_editor').each(function () {

            $(this).val($(this).code());
        })
    }





}

var CRUD = new CRUD(window.crud_object_conf);