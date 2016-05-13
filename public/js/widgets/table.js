;(function($, crud, win){
    var columns = [];
    var i18n = win.i18n;
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
                                buttons += "<a class='text-info' data-id='"+rowData.id+"' data-click='crud_event' data-event='crud.edit_element' style='cursor:pointer;font-size:24px;'><i class='fa fa-edit'> </i></a>&nbsp;&nbsp;&nbsp;";
                            }

                            if (tbl.data('btn_delete'))
                            {
                                buttons += "<a class='text-danger' data-confirm='"+i18n.say('delete_element')+"?' data-id='"+rowData.id+"' data-click='crud_event' data-event='crud.delete_element'  style='cursor:pointer;font-size:24px;'><i class='fa fa-trash-o'> </i></a>";
                            }
                            buttons +='</nobr>';

                            if (tbl.data('list_actions')) {

                                var actions = tbl.data('list_actions');
                                buttons += '<div class="dropdown  dropdown-kebab-pf" style="width:15px;">'
                                    + '<button class="btn btn-link dropdown-toggle" type="button"  data-toggle="dropdown">'
                                    + '<span class="fa fa-ellipsis-v"></span>'
                                    + '</button>'
                                    + '<ul class="dropdown-menu dropdown-menu-right" >';

                                for (var i=0; i<actions.length; i++)
                                {
                                    var com_url = crud.format_setting("model_command_url", {command:actions[i]['command'],id:rowData.id, model:tbl.data('crud_table'), scope:tbl.data('crud_scope')} );
                                    buttons += '<li><a href="'+com_url+'" data-click="crud_action" data-action="crud_command" >';
                                    if (actions[i]['class'])
                                    {
                                        buttons += '<i class="'+actions[i]['class']+'"></i> ';
                                    }
                                    buttons += actions[i]['title']+'</a></li>';
                                    if (i<(actions.length-1))
                                    {
                                        buttons += '<li role="separator" class="divider"></li>';
                                    }
                                }

                                + '</ul>'
                                + '</div>';
                            }
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
                    searching: tbl.data('searchable')?true:false,
                    processing: true,
                    serverSide: true,
                    ajax: crud.format_setting("model_list_url", {model: tbl.data('crud_table'), scope: tbl.data('crud_scope'), uri_params: tbl.data('list_uri_params')}),
                    //columns: crud_cols,
                    order: order,
                    columns: columns,
                    language: {
                        url: "/vendor/crud/js/i18n/vendor/dataTables/"+win.CURRENT_LOCALE+".json"
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
                    alert('No ref table provided');
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
                    alert(i18n.say('no_item_selected'));
                    return;
                }
                if (confirm(i18n.say('delete_selected')+'?'))
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
                var val = null;
                if ($(this).data('default'))
                {
                    if ($(this).attr('multiple')) {
                        val = $(this).data('default').toString();
                        if (val) {

                            val = val.split(',');
                        }
                    } else {
                        val = $(this).data('default')
                    }
                }
                $(this).selectpicker("val", val);
            });

            $('input', $form).each(function (){
                $(this).val($(this).data('default'));
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

})(jQuery, CRUD, window)