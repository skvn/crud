;(function($, crud){
    var columns = [];
    bind_events();
    $.widget("crud.crud_tree", {
        options: {},
        _create: function() {

            var $cont = this.element;


            $.getJSON( crud.format_setting("model_tree_url", {model: $cont.data('crud_tree'), scope: $cont.data('crud_scope')}), function (data) {

                    $cont.jstree({
                            'core': {"data":data, "check_callback" : true},
                            "state": {"key": "tree_" + $cont.data('crud_tree') + "_" + $cont.data('crud_scope')},
                            "plugins": ["dnd", "contextmenu", "search", "state", "wholerow"],
                            "contextmenu":{
                                "items": function($node) {
                                    var tree = $cont.jstree(true);
                                    return {
                                        "create": {
                                            "separator_before": false,
                                            "separator_after": true,
                                            "label": "Создать дочерний элемент",
                                            "icon": "fa fa-plus-square",
                                            "action": function (obj) {

                                                var rargs = {};
                                                rargs[$cont.data('tree_pid_column')] = $node.id;
                                                crud.trigger('crud.edit_element', { id: -1, table: $cont, rargs:rargs })
                                            }
                                        },
                                        "edit": {
                                            "separator_before": false,
                                            "separator_after": true,
                                            "label": "Редактировать",
                                            "icon": "fa fa-edit",
                                            "action": function (obj) {
                                                if ($node.parent == '#')
                                                {
                                                    alert('Нельзя редактировать  корневой элемент');
                                                    return false;
                                                }
                                                crud.trigger('crud.edit_element', { id: $node.id, table: $cont})
                                            }
                                        },
                                        "remove": {
                                            "separator_before": false,
                                            "separator_after": false,
                                            "label": "Удалить",
                                            "icon": "fa fa-trash",
                                            "action": function (obj) {
                                                if ($node.parent == '#')
                                                {
                                                    alert('Невозможно удалить корневой элемент');
                                                    return false;
                                                }
                                            }
                                        }
                                    };
                                }
                            }

                        }
                    );

                }
            );

        },

        reload: function () {
            var $cont = this.element;
            $.getJSON( crud.format_setting("model_tree_url", {model: $cont.data('crud_tree'), scope: $cont.data('crud_scope')}), function (data) {
                $cont.jstree(true).settings.core.data = data;
                $cont.jstree(true).refresh();
            });


        }

    });

    function bind_events()
    {
        crud.bind('crud.update', function(res){
            if (res.success)
            {
                $('div[data-crud_tree='+res.crud_table+']').crud_tree('reload')

            }
        });
    }


})(jQuery, CRUD)