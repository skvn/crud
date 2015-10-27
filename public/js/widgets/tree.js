;(function($, crud){
    var columns = [];
    bind_events();
    $.widget("crud.crud_tree", {
        options: {},
        _create: function() {

            var $cont = this.element;


            $.getJSON( crud.format_setting("model_tree_url", {model: $cont.data('crud_tree'), scope: $cont.data('crud_scope')}), function (data) {

                    $cont.jstree({
                            'core': data,
                            "state": {"key": "tree_" + $cont.data('crud_tree') + "_" + $cont.data('crud_scope')},
                            "plugins": ["dnd", "contextmenu", "search", "state", "wholerow"],
                            "contextmenu":{
                                "items": function($node) {
                                    var tree = $cont.jstree(true);
                                    return {
                                        "Create": {
                                            "separator_before": false,
                                            "separator_after": true,
                                            "label": "Создать дочерний элемент",
                                            "icon": "fa fa-plus-square",
                                            "action": function (obj) {
                                                console.log($node);

                                            }
                                        },
                                        "Rename": {
                                            "separator_before": false,
                                            "separator_after": true,
                                            "label": "Редактировать",
                                            "icon": "fa fa-edit",
                                            "action": function (obj) {

                                            }
                                        },
                                        "Remove": {
                                            "separator_before": false,
                                            "separator_after": false,
                                            "label": "Удалить",
                                            "icon": "fa fa-trash",
                                            "action": function (obj) {

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
        
        refresh: function () {
            //$('#mytree').jstree(true).settings.core.data = new_data;
            //$('#mytree').jstree(true).refresh();
        }

    });

    function bind_events()
    {

    }


})(jQuery, CRUD)