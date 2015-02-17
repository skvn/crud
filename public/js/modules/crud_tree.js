;(function($, window, CRUD){


    $(document).ready(function ()
    {
        init();
        init_events();
    });



    function init()
    {
        $('.crud_tree').treegrid();
        $('.crud_tree').trigger('tree.ready');
        var oldIndex;
        $('.crud_tree').sortable({
            containerSelector: 'table',
            itemPath: '> tbody',
            itemSelector: 'tr.draggable',
            placeholder: '<tr class="placeholder"/>',

            onDragStart: function (item, group, _super) {
                oldIndex = item.index();
                _super(item)
            },

            onDrop: function (item, container, _super) {
                var field,
                    newIndex = item.index();
                var prevId = item.prev().data('id');
                var selfId = item.data('id');
                item.removeClass("dragged").removeAttr("style");
                $("body").removeClass("dragging");
                if (prevId>1) {
                    var prevName = item.prev().find('td').eq(1).text();
                    var curName = item.find('td').eq(1).text();

                    $('.tree_move_upper', $('#tree_move')).text(prevName);
                    $('.tree_move_current', $('#tree_move')).text(curName);
                    $('#tree_move').modal('show');
                    $('#tree_move').data('selfId',selfId);
                    $('#tree_move').data('prevId',prevId);

                } else
                {
                    send_move_tree(selfId, prevId,'makeFirstChild');
                }



            }
        });

        CRUD.init_ichecks();
    }


    function init_events()
    {
        $(document).on('crud.update', function(ev,res)
        {
            // console.log(res);
            //if (res.success)
           // {

                reload_tree();

           // }
        });

        $('.crud_tree').on('tree.ready',  function (){

            CRUD.init_ichecks();

        });

        $('.crud_tree').on('dblclick', 'tbody>tr', function (){

            if ($(this).data('id'))
            {
                CRUD.init_modal($(this).data('id'));
            }


        });

        $('.crud_tree').on('click', '.crud_edit', function (){

            if ($(this).data('id'))
            {
                CRUD.init_modal($(this).data('id'));
            }


        });

        $('*[data-action=move_tree]').on('click', function () {

            send_move_tree($('#tree_move').data('selfId'), $('#tree_move').data('prevId'),$(this).data('command'));
        });




        $('.crud_delete').on('click', function (){
            if (confirm("Все родительские элементы будут удалены вместе с дочерними!\nДействительно удалить выбранные элементы?"))
            {
                var ids =[];
                $('.crud_tree input[data-rel=row]').each(function(){
                    if ($(this).prop('checked'))
                    {
                        if ($(this).parents('tr').data('pid') != '0') {
                            ids.push($(this).val());
                        }
                    }
                })

                if (ids.length)
                {

                    $.post('/admin/crud/'+CRUD.crudObj['class_name']+'/delete',{'ids':ids}, function (res) {
                        $(document).trigger('crud.update',res);
                    })
                }
            }
        });


    }//

    function send_move_tree(node_id, relate_node, command)
    {
        var url = '/admin/crud/'+CRUD.crudObj['class_name']+'/move_tree';
        $('#tree_move').modal('hide');
        $.post(url, {'self_id':node_id,'rel_id':relate_node, 'command': command}, function (res) {
            $(document).trigger('crud.update',res);
        });

    };

    function reload_tree ()
    {
        var url = '/admin/crud/tree/'+CRUD.crudObj['class_name'];
        $('.crud_tree tbody').load(url, function () {
            init();
        });
    }

})(jQuery, window, CRUD)