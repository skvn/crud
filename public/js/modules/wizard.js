;(function($, crud){



    var used_list_aliases = {};

    crud.bind('page.start', function(){
        init();
    });



    function init()
    {
        var crud_actions = {

            wizard_add_list: function (elem) {
                var alias = $.trim($('#list_alias').val());
                if (alias == '')
                {
                    alias = 'default';
                }
                if (!used_list_aliases[alias])
                {
                    used_list_aliases[alias] = 1;
                    var list_html  = $('#list_tpl').html();
                    list_html = list_html.replace(new RegExp("_ALIAS_","g"),alias);
                    $(list_html).appendTo('#lists_container');



                } else {
                    alert('List alias "'+alias+'" already in use. Use another alias');
                    return false;
                }
            }

        };
        crud.add_actions(crud_actions);
    }



})($, CRUD);