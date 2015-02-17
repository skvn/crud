;(function($, window, CRUD){


    $.ajaxSetup({
        headers: {
            'X-CSRF-Token': $('meta[name="csrf-token"]').attr('content')
        }
    });


    var crud_obj = null;

    var crud_actions = {

        refresh_table: function (elem)
        {
            $('.crud_table').DataTable().ajax.reload();
        },
        open_form: function(elem)
        {
            CRUD.init_modal(elem.data("id"), elem.data("model"));
        },
        open_popup: function(elem)
        {
            var popup = elem.data('popup');
            if ($('#'+popup).length <= 0)
            {
                $('<div id="'+popup+'" style="display: none;"></div>').appendTo($(document.body));
            }
            $.get(elem.data('uri'), {}, function(res){
                $('#'+popup).replaceWith(res);
                $('#'+popup).modal('show');
            });
        }

    };


    $(document).ready(function ()
    {
        init();
        init_events();
        CRUD.init_selects($('form'));
        CRUD.init_date_pickers();
    });




    function init()
    {


    }


    function init_events()
    {


        $(document).on('crud.update', function(ev,res)
        {
            // console.log(res);
            if (res.success == false)
            {

                alert(res.error);

            }
        });

        $(document).on('click','*[data-click]', function (e) {

                switch ($(this).data('click'))
                {
                    case 'crud_action':
                        if (crud_actions[$(this).data('action')]) {
                            crud_actions[$(this).data('action')]($(this));
                        } else
                        {
                            alert('undefined action '+ $(this).data('action'));
                        }
                        break;
                }

            }
        );

        $(document).on('click', '.crud_command', function (e) {
            e.preventDefault()
            var conf = $(this).data('confirm');
            var args = $(this).data('args');
            var $self = $(this);
            if (conf)
            {
                if (confirm(conf))
                {
                    send();
                }
            } else
            {
                send();
            }

            function send()
            {

                $.post($self.attr('href'), args, function (res){
                    if (res.success)
                    {
                        if ($self.data('callback_event'))
                        {
                            $(document).trigger($self.data('callback_event'), res);
                        } else if ($self.data('callback')) {
                            eval($self.data('callback'));
                        }
                    } else
                    {
                        alert(res.error);
                    }

                }, 'json')
            }

        });

        $(document).on('click', '.ajax_popup', function (e) {
            e.preventDefault()
            var popup_id = $(this).data('popup');
            $.get($(this).attr('href'), function (data){

                var $node = $(data);
                if ($('#'+popup_id).length)
                {
                    $('#'+popup_id).replaceWith($node);
                } else
                {
                    $node.appendTo($('body'));
                }

                $('#'+popup_id).modal({backdrop:'static', 'show':true});
            });

        });

    }






})(jQuery, window, CRUD)