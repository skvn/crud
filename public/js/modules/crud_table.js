;(function($, crud){

    alert('Deprecated');






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