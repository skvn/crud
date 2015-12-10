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
            },

            wizard_add_relation: function (elem) {
                var type = $('#rtype').val();
                if (type == '')
                {
                    alert('Choose relation type');
                    return;
                }

                $('<a href="#" style="display:none" data-click="crud_action" data-action="clone_fragment" data-skip_arr="1" data-fragment="tpl_rel_'+type+'" data-container="r_container"></a>').appendTo($('body')).click().remove();

            },

            wizard_add_field: function (elem) {
                var type = $('#ftype').val();
                if (type == '')
                {
                    alert('Choose field type');
                    return;
                }

                $('<a href="#" style="display:none" data-click="crud_action" data-action="clone_fragment" data-skip_arr="1" data-fragment="tpl_f_'+type+'" data-container="f_container"></a>').appendTo($('body')).click().remove();

            },


        };
        crud.add_actions(crud_actions);
    }



})($, CRUD);