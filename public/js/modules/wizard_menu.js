;(function($, crud, win){


    crud.bind('page.start', function(){
        init();
    });



    function init()
    {

        $('#menu_tpl').nestable({
            group: 1
        });
        $('#menu_tpl2').nestable({
            group: 1
        });

        var crud_actions = {
            wizard_add_menu_item: function (elem)
            {
                var val = $.trim($('#new_menu_item').val());
                if (val)
                {
                    var $ol = $('#menu_tpl').find('ol.dd-list');
                    var new_length = $ol.find('li').length;
                    $ol.append('<li class="dd-item" data-id="'+new_length+'"><div class="dd-handle">'+val+'</div></li>');
                    $('#new_menu_item').val('');

                }
            }
        };
        crud.add_actions(crud_actions);


    }



})($, CRUD, window);