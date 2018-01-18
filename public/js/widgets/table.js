;(function($, crud, win){
    var i18n = win.i18n;
    bind_events();
    $.widget("crud.crud_list", {
        options: {},
        _create: function()
        {
            
            var tbl = this.element;
            var order = [];

            var idx = 0;
            this.col_list = [];
            var obj = this;
            $("thead th", tbl).each(function(){
                var c = $(this);
                var col;

                col = {name: c.data('list_name'), data: c.data('list_data'), ctype: c.data('list_ctype')};

                col['orderable'] = c.data('list_orderable') == '1';
                if (obj.col_list.length == 0)
                {
                    if (col.ctype === "checkbox")
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            $(td).html('<input data-widget="crud_checkbox" class="i-checks" data-rel="row" type="checkbox" value="' + cellData + '">').data('id',cellData)
                            if (rowData['id'])
                            {
                                $(td).data('id',rowData['id']).attr('data-id', rowData['id']);
                            }
                            else
                            {
                                $(td).data('id',cellData).attr('data-id', cellData);
                            }
                        }
                        //$('thead tr:first td:first',tbl).html('<input class="i-checks" type="checkbox">');
                    }
                    else if (col.ctype === "drag")
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            $(td).addClass("reorder_rows").html('<i class="fa fa-arrows"></i>');
                            if (rowData['id'])
                            {
                                $(td).data('id',rowData['id']).attr('data-id', rowData['id']);
                            }
                            else
                            {
                                $(td).data('id',cellData).attr('data-id', cellData);
                            }
                        }
                    }
                    else
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            if (rowData['id'])
                            {
                                $(td).data('id',rowData['id']).attr('data-id', rowData['id']);
                            }
                            else
                            {
                                $(td).data('id',cellData).attr('data-id', cellData);
                            }
                        }
                    }
                }
                else
                {
                    col.fnCreatedCell = function(td, cellData, rowData, row, col){
                        $(td).attr('id', c.data('list_data')+'_'+rowData.id).data('ref', c.data('list_data')).attr('data-ref', c.data('list_data'));
                    }

                    if (col.ctype === "drag")
                    {
                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            $(td).addClass("reorder_rows").html('<i class="fa fa-arrows"></i>');
                        }
                    }

                    if (col.ctype === "actions")
                    {

                        col.fnCreatedCell = function(td, cellData, rowData, row, col){
                            var buttons = '';

                            if (tbl.data('btn_edit'))
                            {
                                buttons += "<a class='text-info' data-id='"+rowData.id+"' data-click='crud_event' data-event='crud.edit_element' style='cursor:pointer;font-size:24px;'><i class='fa fa-edit'> </i></a>&nbsp;&nbsp;&nbsp;";
                            }

                            if (tbl.data('btn_delete'))
                            {
                                buttons += "<a class='text-danger' data-confirm='"+i18n.say('delete_element')+"?' data-id='"+rowData.id+"' data-click='crud_event' data-event='crud.delete_element'  style='cursor:pointer;font-size:24px;'><i class='fa fa-trash-o'> </i></a>";
                            }


                            if (tbl.data('list_single_actions') && tbl.data('list_single_actions').length) {

                                var actions = tbl.data('list_single_actions');
                                var has_actions = false;
                                //buttons += '<span class="dropdown  dropdown-kebab-pf" style="width:15px;margin-left:10px;">'
                                //    + '<button class="btn btn-link dropdown-toggle" type="button"  data-toggle="dropdown">'
                                //    + '<span class="fa fa-ellipsis-v" style="margin-bottom: 10px;"></span>'
                                //    + '</button>'
                                //    + '<ul class="dropdown-menu dropdown-menu-right" >';
                                //console.log(rowData);
                                for (var i=0; i<actions.length; i++)
                                {
                                    if (actions[i]['ifcolumn'])
                                    {
                                        if (!rowData[actions[i]['ifcolumn']])
                                        {
                                            continue;
                                        }
                                    }

                                    if (!has_actions)
                                    {
                                        buttons += '<span class="dropdown  dropdown-kebab-pf" style="width:15px;margin-left:10px;">'
                                            + '<button class="btn btn-link dropdown-toggle" type="button"  data-toggle="dropdown">'
                                            + '<span class="fa fa-ellipsis-v" style="margin-bottom: 10px;"></span>'
                                            + '</button>'
                                            + '<ul class="dropdown-menu dropdown-menu-right" >';
                                        has_actions = true;
                                    }

                                    if (actions[i]['command'])
                                    {

                                        buttons += '<li><a href="'+actions[i]['command']+'" data-model="'+tbl.data('crud_table')+'" data-scope="'+tbl.data('crud_scope')+'" data-id="'+rowData.id+'" data-click="crud_action" data-action="crud_command" '+ (actions[i]['confirm'] ? 'data-confirm="'+actions[i]['confirm']+'"' : '') +'>';
                                    } else if (actions[i]['event'])
                                    {
                                        var cols = [];
                                        for (var colidx in rowData)
                                        {
                                            cols.push('data-col-'+colidx+'="'+rowData[colidx]+'"');
                                        }
                                        buttons += '<li><a href="#" data-model="'+tbl.data('crud_table')+'" data-scope="'+tbl.data('crud_scope')+'" data-id="'+rowData.id+'" '+cols.join(' ')+' data-click="crud_event" data-event="'+actions[i]['event']+'" '+ (actions[i]['confirm'] ? 'data-confirm="'+actions[i]['confirm']+'"' : '') +'>';
                                    }
                                    else if (actions[i]['popup'])
                                    {
                                        buttons += '<li><a href="'+actions[i]['popup']+'"  data-title="'+actions[i]['title']+'" data-model="'+tbl.data('crud_table')+'" data-id="'+rowData.id+'" data-scope="'+tbl.data('crud_scope')+'" data-click="crud_popup" data-popup="'+actions[i]['popup_id']+'" data-event="'+actions[i]['event']+'" '+ (actions[i]['confirm'] ? 'data-confirm="'+actions[i]['confirm']+'"' : '') +'>';
                                    }
                                    else if (actions[i]['url'])
                                    {
                                        var strargs = {
                                            model : tbl.data('crud_table'),
                                            id: rowData.id,
                                            scope: tbl.data('crud_scope')
                                        };
                                        buttons += '<li><a href="'+crud.format_string(actions[i]['url'], strargs)+'"  data-title="'+actions[i]['title']+'"  target="_blank" '+ (actions[i]['confirm'] ? 'data-confirm="'+actions[i]['confirm']+'"' : '') +'>';
                                    }


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
                                if (has_actions)
                                {
                                    buttons += '</ul>'
                                    + '</span>';
                                }
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
                obj.col_list.push(col);
            });

            //console.log(cols);
            //var list_name = crud.crudObj.list_name ? crud.crudObj.list_name : 'index';
            var rowCallBack = crud.win.crudRowCallback ? crud.win.crudRowCallback : null;
            var dtConfig = {
                searching: tbl.data('searchable')?true:false,
                processing: true,
                serverSide: true,
                pageLength: 30,
                lengthMenu: [10, 30, 50, 100],

                ajax: crud.format_setting("model_list_url", {model: tbl.data('crud_table'), scope: tbl.data('crud_scope'), url_params: tbl.data('list_url_params')}),
                order: order,
                //rowReorder: {
                //    update: false,
                //    dataSrc: 'id'
                //},
                autoWidth: false,
                columns: this.col_list,
                language: {
                    url: "/vendor/crud/js/i18n/vendor/dataTables/"+win.CURRENT_LOCALE+".json"
                },
                rowCallback: rowCallBack
            };
            if (tbl.data('rows_draggable'))
            {
                dtConfig.rowReorder = {
                    update: false,
                    dataSrc: tbl.data('rows.draggable'),
                    selector: 'td.reorder_rows'
                };
                tbl.on('row-reorder.dt', function ( e, diff, edit)
                {
                    if (diff.length > 0)
                    {
                        var reorder = {};
                        for (var i in diff)
                        {
                            reorder[$('td[data-id]', diff[i].node).data('id')] = diff[i].newPosition+1;
                        }
                        var trigger = $('#' + tbl.data('list_table_ref') + '_reorder_trigger');
                        trigger.data('args', {'reorder': reorder}).click();
                        //$('#trigger_reorder').data('args', {'reorder': reorder}).click();
                    }
                });
            }

            if (tbl.data('list_type') == 'dt_tree')
            {

                dtConfig['processing'] = false;
                dtConfig['serverSide']  = false;
                dtConfig['ajax'] = {url:dtConfig['ajax'], data: {parent_id:0}};
                dtConfig['ordering'] =  false;
                dtConfig['paging'] =  false;
                dtConfig['treetable'] =  {
                    branchAttr: '__has_children',
                    nodeIdAttr: tbl.data('key_name'),
                    parentIdAttr: tbl.data('parent_id'), 
                    expandable: true,
                        onNodeExpand: function() {
                            var node = this;
                            var url = tbl.DataTable().ajax.url();
                            var params = {parent_id:node.id, columns:tbl.DataTable().ajax.params()['columns']};
                            $.getJSON(url, params)
                                .done(function(json) {
                                    tbl.DataTable().treeTable.addChildren(node, json.data);
                                } )
                                .fail(function(e) {
                                    console.log("error", e);
                                } )
                        ;
                    }
                }
            }
            tbl.dataTable(dtConfig);

            tbl.on( 'draw.dt', function (e, o) {
                init_checkboxes(obj.col_list, this.element);
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
            });
            //tbl.on('row-reorder.dt', function ( e, diff, edit)
            //{
            //    alert(diff);
            //    alert(edit);
            //    console.log(diff);
            //    console.log(edit);
            //
            //    if (diff.length > 0)
            //    {
            //        alert($('td[data-id]', diff[0].node).data('id') + '<->' + $('td[data-id]', diff[diff.length-1].node).data('id'));
            //    }
            //} );
            if (tbl.data('form_type') == 'tabs')
            {
                var id = crud.loc.hash.substr(1);
                var model = tbl.data('crud_table') ? tbl.data('crud_table') : tbl.data('crud_tree');
                if (!isNaN(id) && id)
                {
                    crud.init_edit_tab(model, id, {table: tbl, scope: tbl.data('crud_scope')});
                }
            }
        }
    });

    function init_checkboxes(cols, cont)
    {
        if (cols[0]['ctype'] && cols[0]['ctype'] == 'checkbox')
        {
            var all_chck = $('<input data-widget="crud_checkbox" class="i-checks" type="checkbox">');
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
        crud.trigger('form.init', {form: cont});
        //crud.init_ichecks();

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
                $('table[data-crud_table]').each(function(){
                    var t = $(this);
                    if (res.crud_table && res.crud_table != t.data('crud_table'))
                    {
                        return;
                    }
                    t.DataTable().ajax.reload(null, false);
                });
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
            crud.trigger('form.before_submit', {form: $form});
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: crud.format_setting("model_filter_url", {model: $form.data('crud_model'), scope: $form.data('crud_scope')}),
                    //url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.trigger('form.after_submit', {form: $form});
                        //crud.toggle_form_progress($form)
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
                    if ($(this).data('default') === '*') {
                        $('option', $(this)).attr('selected', 'selected');
                        //$(this).selectpicker("selectAll");
                        $(this).selectpicker("refresh");
                        return;
                        //val = [];
                        //$("option", $(this)).each(function () {
                        //    val.push($(this).attr('value'));
                        //});
                    }
                }
                $(this).selectpicker("val", val);
            });

            $('input', $form).each(function (){
                var e = $(this);
                if (e.is(':checkbox')) {
                    var hidden = $('input[type=hidden]', e.parent().parent());
                    e.prop('checked', hidden.data('default') == 1 ? true : false).iCheck('update').trigger('ifChanged');
                } else {
                    e.val($(this).data('default'));
                }
            });


            crud.trigger('form.before_submit', {form: $form});
            $form.ajaxSubmit(
                {
                    type:'POST',
                    url: crud.format_setting('model_filter_url', {model: $form.data('crud_model'), scope: $form.data('crud_scope')}),
                    //url: '/admin/crud/'+crud.crudObj['class_name']+'/filter/'+$(this).data('crud_context'),
                    dataType: 'json',
                    success: function (res) {
                        crud.trigger('form.after_submit', {form: $form});
                        //crud.toggle_form_progress($form)
                        crud.trigger('crud.filter_set',res);
                    }

                }
            );
            crud.trigger('crud.filter_reset');

        });


    }

})(jQuery, CRUD, window)