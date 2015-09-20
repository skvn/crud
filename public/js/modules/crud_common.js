;(function($, crud){


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
            crud.init_modal(elem.data("id"), elem.data("model"));
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
                $(crud.doc).trigger("crud.content_loaded", {cont: $('#'+popup)});
                $('#'+popup).modal('show');
                crud.init_ichecks($('#'+popup));
            });
        },
        call_uri: function(elem)
        {
            $.get(elem.data('uri'), $.extend({}, elem.data()), function(res){
                alert(res['message']);
            });
        }

    };


    $(crud.doc).ready(function ()
    {
        init_events();
        crud.init_selects($('form'));
        crud.init_date_pickers();
    });






    function init_events()
    {


        $(crud.doc).on('crud.update', function(ev,res)
        {
            // console.log(res);
            if (res.success == false)
            {

                alert(res.error);

            }
        });

        $(crud.doc).on('click','*[data-click]', function (e)
            {
                e.preventDefault();
                if ($(this).data('confirm'))
                {
                    crud.con.log($(this).data('confirm'));
                    if (!confirm($(this).data('confirm')))
                    {
                        return;
                    }
                }

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

        $(crud.doc).on('click', '.crud_command', function (e) {
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
                            $(crud.doc).trigger($self.data('callback_event'), res);
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

        $(crud.doc).on('click', '.ajax_popup', function (e) {
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






})(jQuery, CRUD)