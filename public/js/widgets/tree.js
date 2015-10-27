;(function($, crud){
    var columns = [];
    bind_events();
    $.widget("crud.crud_tree", {
        options: {},
        _create: function() {

            var $cont = this.element;
            $.getJSON( crud.format_setting("model_tree_url", {model: $cont.data('crud_tree'), scope: $cont.data('crud_scope')}), function (data) {

                    $cont.jstree({
                        'core' : data,
                        "state" : { "key" : "tree_"+$cont.data('crud_tree')+"_"+$cont.data('crud_scope') },
                        "plugins" : [ "dnd", "contextmenu", "search", "state", "wholerow"  ] });

                }
            );

        }

    });

    function bind_events()
    {

    }


})(jQuery, CRUD)