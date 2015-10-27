;(function($, crud){
    var columns = [];
    bind_events();
    $.widget("crud.crud_tree", {
        options: {},
        _create: function() {

            var $cont = this.element;
            var $s_input = $('#search_'+$cont.data('list_table_ref'));
            var to = false;
            $s_input.keyup(function () {
                if (to) {
                    clearTimeout(to);
                }
                to = setTimeout(function () {
                    var v = $s_input.val();
                    $cont.jstree(true).search(v);
                }, 250);
            });

            $.getJSON( crud.format_setting("model_tree_url", {model: $cont.data('crud_tree'), scope: $cont.data('crud_scope')}), function (data) {

                    $cont.jstree({
                            'core': {
                                "data":data,
                                "check_callback" :  function (operation, node, node_parent, node_position, more) {
                                    // operation can be 'create_node', 'rename_node', 'delete_node', 'move_node' or 'copy_node'
                                    if (operation == 'move_node') {
                                        if (node_parent.id == '#') {
                                            return false;
                                        }
                                        crud.trigger('crud.move_tree_element', {
                                            id: node.id,
                                            crud_model: $cont.data('crud_tree'),
                                            parent_id: node_parent.id,
                                            position: node_position
                                        })
                                    }
                                }
                            },
                            "dnd":{
                                "check_while_dragging":false
                            },
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
                                                if ($node.children.length)
                                                {
                                                    alert('Невозможно удалить, сначала переместите дочерние элементы.');
                                                    return false;
                                                }

                                                
                                                crud.trigger('crud.delete_element', { id: $node.id, crud_table: $cont.data('crud_tree')})
                                                tree.delete_node($node);

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
        crud.bind('crud.reload', function(res){
            if (res.success)
            {
                $('div[data-crud_tree='+res.crud_table+']').crud_tree('reload')

            }
        });

        crud.bind('crud.move_tree_element', function (args){

            var url = crud.format_setting("model_move_tree_url", {model: args.crud_model});
            $.post(url, args,function (res) {

            });

        });

    }


})(jQuery, CRUD)