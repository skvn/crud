;(function($, crud){
    bind_events();
    $.widget("crud.crud_tree_control", {
        options: {},
        _create: function() {

            var $cont = this.element;
            var $s_input = $('#search_'+$cont.attr('id'));
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

            
            $.getJSON(crud.format_setting("model_tree_options_url", {model: $cont.data('model'), id : $cont.data('model_id'), field:$cont.data('name')}), function (data) {
                $cont.jstree({
                    'core': {
                        'data': data
                    },
                    "checkbox": {
                        "keep_selected_style": false,
                        "three_state": true
                        //"tie_selection": false
                    },
                    "plugins": ["checkbox", "search"]
                });
            });
        //
        //     var to = false;
        //     $('#search_medcats').keyup(function () {
        //         if(to) {
        //             clearTimeout(to);
        //         }
        //         to = setTimeout(function () {
        //             var v = $('#search_medcats').val();
        //             $('#medcat_tree').jstree(true).search(v);
        //         }, 250);
        //     });
        //
        //     $("#medcat_tree").bind("changed.jstree", function (e, d) {
        //
        //         var selected  = $("#medcat_tree").jstree('get_checked');
        //         if (selected.length)
        //         {
        //             console.log(selected);
        //             $('#medcattrg').val(selected.join(' '));
        //         }
        //
        //
        //     });

            
         }



    });

    function bind_events()
    {


    }


})(jQuery, CRUD)