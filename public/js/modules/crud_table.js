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
        //init_table();
        init_events();
    });



    //function init_table()
    //{
    //    if (crud.crudObj && $('.crud_table').length)
    //    {
    //        var crud_cols = crud.crudObj.list.columns;
    //        if (crud_cols[0]['ctype'] && crud_cols[0]['ctype'] == 'checkbox')
    //        {
    //            crud_cols[0]["fnCreatedCell"] = function (td, cellData, rowData, row, col) {
    //                $(td).html('<input class="i-checks" data-rel="row" type="checkbox" value="' + cellData + '">').data('id',cellData)
    //            };
    //            $('.crud_table thead tr:first td:first').html('<input class="i-checks" type="checkbox">');
    //        } else
    //        {
    //            crud_cols[0]["fnCreatedCell"] = function (td, cellData, rowData, row, col) {
    //
    //                $(td).data('id',cellData);
    //            };
    //        }
    //
    //        for (var i=0; i<crud_cols.length; i++)
    //        {
    //            if (i>0) {
    //                crud_cols[i]["fnCreatedCell"] = function (td, cellData, rowData, row, col)
    //                {
    //                   $(td).attr('id',crud_cols[col]['data']+'_'+rowData.id);
    //                };
    //            }
    //
    //            if (crud_cols[i].hint)
    //            {
    //                if (!crud_cols[i].ctype)
    //                {
    //                    crud_cols[i].title += ' ' + crud_tooltip_pattern.replace('%s', crud_cols[i].hint['index']).replace('%t', crud_cols[i].hint['default']);
    //                }
    //            }
    //        }
    //        //console.log(cols);
    //        if ($('.crud_table').length) {
    //            var list_name = crud.crudObj.list_name ? crud.crudObj.list_name : 'index';
    //            var rowCallBack = crud.win.crudRowCallback ? crud.win.crudRowCallback : null;
    //            $('.crud_table').dataTable(
    //                {
    //                    searching: false,
    //                    processing: true,
    //                    serverSide: true,
    //                    ajax: crud.format_setting("model_list_url", {model: crud.crudObj.class_name, scope: crud.crudObj.scope}),
    //                    columns: crud_cols,
    //                    language: {
    //                        url: "/vendor/crud/js/plugins/dataTables/lang/russian.json"
    //                    },
    //                    rowCallback: rowCallBack
    //
    //
    //                }
    //            );
    //        }
    //
    //    }
    //}


    function init_events()
    {


        //crud.bind('crud.delete_element', function(data){
        //    // alert(data.el.data('id'));
        //});
        //
        //
        ////
        //crud.bind('crud.update', function(res){
        //    if (res.success)
        //    {
        //        //$('.crud_table').DataTable().ajax.reload(null, false);
        //        $('table[data-crud_table]').DataTable().ajax.reload(null, false);
        //    }
        //});
        //
        //crud.bind('crud.reload', function(res){
        //    if (res.success)
        //    {
        //        //$('.crud_table').DataTable().ajax.reload(null, false);
        //        $('table[data-crud_table]').DataTable().ajax.reload(null, false);
        //    }
        //});
        //
        //crud.bind('crud.filter_set', function(res){
        //    if (res.success)
        //    {
        //        //$('.crud_table').DataTable().ajax.reload();
        //        $('table[data-crud_table]').DataTable().ajax.reload(null, false);
        //    }
        //});
        //
        //crud.bind('crud.delete', function(res){
        //    if (res.success)
        //    {
        //        //$('.crud_table').DataTable().ajax.reload(null, false);
        //        $('table[data-crud_table]').DataTable().ajax.reload(null, false);
        //    }
        //});

        //$('.crud_table').on('dblclick', 'tbody>tr', function (){
        //
        //    if ($(this).parents('.crud_table').first().data('crud_noedit') == '1')
        //    {
        //        return;
        //    }
        //
        //    crud.trigger('crud.edit_element', {el:$(this).find('td').first()});
        //
        //    //crud.init_modal($(this).find('td').first().data('id'));
        //})

        //$('.crud_table').on( 'draw.dt', function (e, o) {
        //    init_checkboxes();
        //    crud.trigger('crud.content_loaded', {cont: $(e.target)});
        //} );

        //$('.crud_delete').on('click', function (e){
        //    e.preventDefault();
        //    if (confirm('Действительно удалить выбранные элементы?'))
        //    {
        //        var ids =[];
        //        var scope;
        //        if ($("table[data-crud_table]").length > 0)
        //        {
        //            scope = $("table[data-crud_table]");
        //        }
        //        else
        //        {
        //            scope = $(".crud_table");
        //        }
        //        $('input[data-rel=row]', scope).each(function(){
        //            if ($(this).prop('checked'))
        //            {
        //                ids.push($(this).val());
        //            }
        //        })
        //
        //        if (ids.length)
        //        {
        //
        //            $.post(crud.format_setting('model_delete_url', {model: crud.crudObj['class_name']}),{'ids':ids}, function (res) {
        //                crud.trigger('crud.delete',res);
        //            })
        //        }
        //    }
        //});

        //$('.crud_table_command').on('click', function ()
        //{
        //        var ids =[];
        //        $('.crud_table input[data-rel=row]').each(function(){
        //            if ($(this).prop('checked'))
        //            {
        //                ids.push($(this).val());
        //            }
        //        })
        //        if (ids.length)
        //        {
        //            $(this).data('args',{ids:ids});
        //        }
        //})

    }

    //function init_checkboxes()
    //{
    //    if (crud.crudObj) {
    //        var crud_cols = crud.crudObj.list.columns;
    //        if (crud_cols[0]['ctype'] && crud_cols[0]['ctype'] == 'checkbox') {
    //
    //            var all_chck = $('<input class="i-checks" type="checkbox">');
    //            all_chck.on('ifChecked', function (){
    //                $('.crud_table tr td').find('input.i-checks').iCheck('check');
    //            });
    //            all_chck.on('ifUnchecked', function (){
    //                $('.crud_table tr td').find('input.i-checks').iCheck('uncheck');
    //            });
    //            $('.crud_table thead tr:first th:first').removeClass('sorting_asc').html('').append(all_chck.clone(true));
    //            $('.crud_table tfoot tr:first th:first').html('').append(all_chck);
    //        }
    //    }
    //
    //    crud.init_ichecks();
    //
    //}

})(jQuery, CRUD)