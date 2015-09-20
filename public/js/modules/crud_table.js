;(function($, crud){

    var crud_actions = {
            refresh_table: function (elem)
            {
                $('.crud_table').DataTable().ajax.reload();
            }

    };

    crud.bind('page.start', function()
    {
        crud.add_actions(crud_actions);
        init();
        init_events();
    });
    //$(crud.doc).ready(function ()
    //{
    //    init();
    //    init_events();
    //});





    function init()
    {
        if (crud.crudObj)
        {
            var crud_cols = crud.crudObj.list.columns;
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

                if (i>0) {
                    crud_cols[i]["fnCreatedCell"] = function (td, cellData, rowData, row, col) {

                       $(td).attr('id',crud_cols[col]['data']+'_'+rowData.id);

                    };
                }

                if (crud_cols[i].hint)
                {
                    if (!crud_cols[i].ctype)
                    {
                        crud_cols[i].title += ' ' + crud_tooltip_pattern.replace('%s', crud_cols[i].hint['index']).replace('%t', crud_cols[i].hint['default']);
                    }
                }
            }
            //console.log(cols);
            if ($('.crud_table').length) {
                var list_name = crud.crudObj.list_name ? crud.crudObj.list_name : 'index';
                var rowCallBack = crud.win.crudRowCallback ? crud.win.crudRowCallback : null;
                $('.crud_table').dataTable(
                    {


                        "searching": false,
                        "processing": true,
                        "serverSide": true,
                        "ajax": "/admin/crud/"+crud.crudObj.class_name +"/list/"+list_name+"?list_context="+crud.crudObj.context,
                        "columns": crud_cols,
                        "language": {
                            "url": "/vendor/crud/js/plugins/dataTables/lang/russian.json"
                        },
                        "rowCallback": rowCallBack


                    }
                );
            }

        }
    }


    function init_events()
    {
        crud.bind('crud.update', function(res){
            if (res.success)
            {
                $('.crud_table').DataTable().ajax.reload(null, false);
            }
        });

        //$(crud.doc).on('crud.update', function(ev,res)
        //{
        //    //alert(11);
        //   // console.log(res);
        //    if (res.success)
        //    {
        //
        //        $('.crud_table').DataTable().ajax.reload(null, false);
        //
        //    }
        //});

        crud.bind('crud.reload', function(res){
            if (res.success)
            {
                $('.crud_table').DataTable().ajax.reload(null, false);
            }
        });
        //$(crud.doc).on('crud.reload', function(ev,res)
        //{
        //
        //    if (res.success)
        //    {
        //
        //        $('.crud_table').DataTable().ajax.reload(null, false);
        //
        //    }
        //});

        crud.bind('crud.filter_set', function(res){
            if (res.success)
            {
                $('.crud_table').DataTable().ajax.reload();
            }
        });
        //$(crud.doc).on('crud.filter_set', function(ev,res)
        //{
        //    // console.log(res);
        //    if (res.success)
        //    {
        //
        //        $('.crud_table').DataTable().ajax.reload();
        //    }
        //});
        crud.bind('crud.delete', function(res){
            if (res.success)
            {
                $('.crud_table').DataTable().ajax.reload(null, false);
            }
        });

        //$(crud.doc).on('crud.delete', function(ev,res)
        //{
        //    // console.log(res);
        //    if (res.success)
        //    {
        //        $('.crud_table').DataTable().ajax.reload(null, false);
        //    }
        //});

        $('.crud_table').on('dblclick', 'tbody>tr', function (){

            if ($(this).parents('.crud_table').first().data('crud_noedit') == '1')
            {
                return;
            }
            crud.init_modal($(this).find('td').first().data('id'));


        })

        $('.crud_table').on( 'draw.dt', function (e, o) {
            init_checkboxes();
            crud.trigger('crud.content_loaded', {cont: $(e.target)});
            //$(crud.doc).trigger("crud.content_loaded", {cont: $(e.target)});
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

                    $.post('/admin/crud/'+crud.crudObj['class_name']+'/delete',{'ids':ids}, function (res) {
                        crud.trigger('crud.delete',res);
                        //$(crud.doc).trigger('crud.delete',res);
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
        if (crud.crudObj) {
            var crud_cols = crud.crudObj.list.columns;
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

        crud.init_ichecks();

    }

})(jQuery, CRUD)