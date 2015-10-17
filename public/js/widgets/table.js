;(function($, crud){
    var columns = [];
    bind_events();
    $.widget("crud.crud_list", {
        options: {},
        _create: function()
        {
            
            var tbl = this.element;
            var order = [];

            var idx = 0;
            $("thead th", tbl).each(function(){
                var c = $(this);
                var col;

                col = {name: c.data('list_name'), data: c.data('list_data'), ctype: c.data('list_ctype')};

                col['orderable'] = c.data('list_orderable') == '1';
                if (columns.length == 0)
                {
                    if (c.data('list_ctype') === "checkbox")
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            $(td).html('<input class="i-checks" data-rel="row" type="checkbox" value="' + cellData + '">').data('id',cellData)
                        }
                        //$('thead tr:first td:first',tbl).html('<input class="i-checks" type="checkbox">');
                    }
                    else
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            $(td).data('id',cellData);
                        }
                    }
                }
                else
                {
                    col.fnCreatedCell = function(td, cellData, rowData, row, col){
                        $(td).attr('id', c.data('list_data')+'_'+rowData.id);
                    }

                    if (c.data('list_ctype') === "actions")
                    {

                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            var buttons = '<nobr>';

                            if (tbl.data('btn_edit'))
                            {
                                buttons += "<a class='text-info' data-id='"+rowData.id+"' data-click='crud_event' data-event='crud.edit_element' style='font-size:24px;'><i class='fa fa-edit'> </i></a>&nbsp;&nbsp;&nbsp;";
                            }

                            if (tbl.data('btn_delete'))
                            {
                                buttons += "<a class='text-danger' data-confirm='Действительно удалить элемент?' data-id='"+rowData.id+"' data-click='crud_event' data-event='crud.delete_element' data- style='font-size:24px;'><i class='fa fa-trash-o'> </i></a>";
                            }
                            buttons +='</nobr>';
                            $(td).html(buttons);
                        }

                    }
                }


                if (c.data('default_order'))
                {
                    order.push([idx,c.data('default_order')]);
                }

                idx ++;
                columns.push(col);
            });

            //console.log(cols);
            //var list_name = crud.crudObj.list_name ? crud.crudObj.list_name : 'index';
            var rowCallBack = crud.win.crudRowCallback ? crud.win.crudRowCallback : null;
            tbl.dataTable({
                    searching: false,
                    processing: true,
                    serverSide: true,
                    ajax: crud.format_setting("model_list_url", {model: tbl.data('crud_table'), scope: tbl.data('crud_scope')}),
                    //columns: crud_cols,
                    order: order,
                    columns: columns,
                    language: {
                        url: "/vendor/crud/js/plugins/dataTables/lang/russian.json"
                    },
                    rowCallback: rowCallBack


            });
            tbl.on( 'draw.dt', function (e, o) {
                init_checkboxes(columns, this.element);
                crud.trigger('crud.content_loaded', {cont: $(e.target)});
            } );
            tbl.on('dblclick', 'tbody>tr', function (){

                if (tbl.data('crud_noedit') == '1')
                {
                    return;
                }
                //crud.trigger('crud.edit_element', {el:$(this).find('td').first()});
                crud.trigger('crud.edit_element', { id: $('td:first', $(this)).data('id'), table: tbl})
                //crud.init_modal($(this).find('td').first().data('id'));
            })
        }
    });

    function init_checkboxes(cols, cont)
    {
        if (cols[0]['ctype'] && cols[0]['ctype'] == 'checkbox')
        {
            var all_chck = $('<input class="i-checks" type="checkbox">');
            all_chck.on('ifChecked', function ()
            {
                $('tr td', cont).find('input.i-checks').iCheck('check');
            });
            all_chck.on('ifUnchecked', function ()
            {
                $('tr td', cont).find('input.i-checks').iCheck('uncheck');
            });
            $('thead tr:first th:first', cont).removeClass('sorting_asc').html('').append(all_chck.clone(true));
            $('tfoot tr:first th:first', cont).html('').append(all_chck);
        }

        crud.init_ichecks();

    }

    function bind_events()
    {

        var crud_actions = {
            refresh_table: function (elem)
            {
                if (elem.data('ref'))
                {
                    $('table[data-list_table_ref='+elem.data('ref')+']').DataTable().ajax.reload();
                }
                else
                {
                    $('table[data-crud_table]').each(function(){
                        $(this).DataTable().ajax.reload();
                    });
                }
            },
            table_mass_delete: function(elem)
            {
                if (!elem.data('ref'))
                {
                    alert('Не указана таблица для удаления элементов');
                    return;
                }
                var tbl = $('table[data-list_table_ref='+elem.data('ref')+']');
                var ids = [];
                $('input[data-rel=row]', tbl).each(function(){
                    if ($(this).prop('checked'))
                    {
                        ids.push($(this).val());
                    }
                });
                if (ids.length <= 0)
                {
                    alert('Не выбрано ни одного элемена');
                    return;
                }
                if (confirm('Действительно удалить выбранные элемены ?'))
                {
                    $.post(crud.format_setting('model_delete_url', {model: tbl.data('crud_table')}),{ids:ids}, function (res) {
                        crud.trigger('crud.delete',res);
                    })
                }

            }
        };
        crud.add_actions(crud_actions);


        crud.bind('crud.delete_element', function(data){


            if (data['id'])
            {
                var ids = [data['id']];
                if (data.table)
                {
                    data.table.find('td#id_'+data['id']).parent().remove();
                }
                $.post(crud.format_setting('model_delete_url', {model: data.crud_table}),{'ids':ids}, function (res) {
                    crud.trigger('crud.delete',res);
                })
            }
        });


        //
        crud.bind('crud.update', function(res){
            if (res.success)
            {
                //$('.crud_table').DataTable().ajax.reload(null, false);
                $('table[data-crud_table]').DataTable().ajax.reload(null, false);
            }
        });

        crud.bind('crud.reload', function(res){
            if (res.success)
            {
                //$('.crud_table').DataTable().ajax.reload(null, false);
                $('table[data-crud_table]').DataTable().ajax.reload(null, false);
            }
        });

        crud.bind('crud.filter_set', function(res){
            if (res.success)
            {
                //$('.crud_table').DataTable().ajax.reload();
                $('table[data-crud_table]').DataTable().ajax.reload(null, false);
            }
        });

        crud.bind('crud.delete', function(res){
            if (res.success)
            {
                //$('.crud_table').DataTable().ajax.reload(null, false);
                $('table[data-crud_table]').DataTable().ajax.reload(null, false);
            }
        });

        $(crud.doc).on('submit', '#crud_filter_form', function (e) {
            e.preventDefault();
            var $form = $(this);
            crud.toggle_form_progress($form);
            crud.init_form_progress($form);
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: crud.format_setting("model_filter_url", {model: $form.data('crud_model'), scope: $form.data('crud_scope')}),
                    //url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.toggle_form_progress($form)
                        crud.trigger('crud.filter_set', res);

                    }

                }
            );

        });

        $(crud.doc).on('reset', '#crud_filter_form', function (e) {
            //e.preventDefault();
            var $form = $(this);
            $('select', $form).each(function (){
                $(this).select2("val", null);
            });

            $('input', $form).each(function (){
                $(this).val('');
            });

            crud.toggle_form_progress($form);
            crud.init_form_progress($form);
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: crud.format_setting('model_filter_url', {model: $form.data('crud_model'), scope: $form.data('crud_scope')}),
                    //url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.toggle_form_progress($form)
                        crud.trigger('crud.filter_set',res);
                    }

                }
            );

        });


    }

})(jQuery, CRUD)