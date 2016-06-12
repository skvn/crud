;(function($, crud){
    $.widget("crud.crud_tree_control", {
        options: {},
        _create: function() {

            var $cont = this.element;
            var control_id = $cont.attr('id');
            var control_name = $cont.data('name');
            var $s_input = $('#search_'+control_id);

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

            $cont.bind("changed.jstree", function (e, d) {

                var selected  = $cont.jstree('get_checked');
                var value = [];
                if (selected.length)
                {
                    $.each(selected, function (i,row) {
                        if (row.indexOf(control_name)>=0)
                        {
                            value.push(row.replace(control_name+'-',''));
                        }
                    });

                }

                //console.log(value);
                $('#hidden_'+control_id).val(value);


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

         }



    });

    


})(jQuery, CRUD)