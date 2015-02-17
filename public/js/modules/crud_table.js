;(function($, window, CRUD){


    var crud_obj = null
    $(document).ready(function ()
    {
        init();
        init_events();
    });





    function init()
    {
        if (window.crud_object_conf) {
            crud_obj = window.crud_object_conf;
        }

        if (crud_obj)
        {
            var crud_cols = crud_obj.list.columns;
            if (crud_cols[0]['ctype'] && crud_cols[0]['ctype'] == 'checkbox')
            {
                crud_cols[0]["fnCreatedCell"] = function (td, cellData, rowData, row, col) {
                    $(td).html('<input class="i-checks" data-rel="row" type="checkbox" value="' + cellData + '">').data('id',cellData)
                };
                $('.crud_table thead tr:first td:first').html('<input class="i-checks" type="checkbox">');
            } else
            {
                crud_cols[0]["fnCreatedCell"] = function (td, cellData, rowData, row, col) {
                    $(td).data('id',cellData);
                };
            }
            for (var i=0; i<crud_cols.length; i++)
            {
                if (crud_cols[i].hint)
                {
                    if (!crud_cols[i].ctype)
                    {
                        crud_cols[i].title += ' ' + crud_tooltip_pattern.replace('%s', crud_cols[i].hint);
                    }
                }
            }
            //console.log(cols);
            if ($('.crud_table').length) {
                var list_name = crud_obj.list_name?crud_obj.list_name:'index';
                var table = $('.crud_table').dataTable(
                    {

                        "searching": false,
                        "processing": true,
                        "serverSide": true,
                        "ajax": "/admin/crud/"+crud_obj.class_name +"/list/"+list_name+"?list_context="+crud_obj.context,
                        "columns": crud_cols,
                        "language": {
                            "url": "/vendor/crud/js/plugins/dataTables/lang/russian.json"
                        }


                    }
                );
            }

        }
    }


    function init_events()
    {

        $(document).on('crud.update', function(ev,res)
        {
            //alert(11);
           // console.log(res);
            if (res.success)
            {

                $('.crud_table').DataTable().ajax.reload(null, false);

            }
        });

        $(document).on('crud.reload', function(ev,res)
        {

            if (res.success)
            {

                $('.crud_table').DataTable().ajax.reload(null, false);

            }
        });

        $(document).on('crud.filter_set', function(ev,res)
        {
            // console.log(res);
            if (res.success)
            {

                $('.crud_table').DataTable().ajax.reload();
            }
        });

        $(document).on('crud.delete', function(ev,res)
        {
            // console.log(res);
            if (res.success)
            {
                $('.crud_table').DataTable().ajax.reload(null, false);
            }
        });

        $('.crud_table').on('dblclick', 'tbody>tr', function (){

            CRUD.init_modal($(this).find('td').first().data('id'));


        })

        $('.crud_table').on( 'draw.dt', function (e, o) {
            init_checkboxes();
            $(document).trigger("crud.content_loaded", {cont: $(e.target)});
        } );




        $('.crud_delete').on('click', function (){
            if (confirm('Действительно удалить выбранные элементы?'))
            {
                var ids =[];
                $('.crud_table input[data-rel=row]').each(function(){
                    if ($(this).prop('checked'))
                    {
                        ids.push($(this).val());
                    }
                })

                if (ids.length)
                {

                    $.post('/admin/crud/'+CRUD.crudObj['class_name']+'/delete',{'ids':ids}, function (res) {
                        $(document).trigger('crud.delete',res);
                    })
                }
            }
        });

        $('.crud_table_command').on('click', function ()
        {

                var ids =[];
                $('.crud_table input[data-rel=row]').each(function(){
                    if ($(this).prop('checked'))
                    {
                        ids.push($(this).val());
                    }
                })

                if (ids.length)
                {

                    $(this).data('args',{ids:ids});
                }

        })



    }

    function init_checkboxes()
    {
        if (crud_obj) {
            var crud_cols = crud_obj.list.columns;
            if (crud_cols[0]['ctype'] && crud_cols[0]['ctype'] == 'checkbox') {

                var all_chck = $('<input class="i-checks" type="checkbox">');
                all_chck.on('ifChecked', function (){
                    $('.crud_table tr td').find('input.i-checks').iCheck('check');
                });
                all_chck.on('ifUnchecked', function (){
                    $('.crud_table tr td').find('input.i-checks').iCheck('uncheck');
                });
                $('.crud_table thead tr:first th:first').removeClass('sorting_asc').html('').append(all_chck.clone(true));
                $('.crud_table tfoot tr:first th:first').html('').append(all_chck);
            }
        }

        CRUD.init_ichecks();

    }

})(jQuery, window, CRUD)