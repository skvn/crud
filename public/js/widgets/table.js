;(function($, crud){
    var columns = [];
    $.widget("crud.crud_list", {
        options: {},
        _create: function()
        {
            
            var tbl = this.element;
            $("thead th", this.element).each(function(){
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



                columns.push(col);
            });

            //console.log(cols);
            var list_name = crud.crudObj.list_name ? crud.crudObj.list_name : 'index';
            var rowCallBack = crud.win.crudRowCallback ? crud.win.crudRowCallback : null;
            this.element.dataTable({
                    searching: false,
                    processing: true,
                    serverSide: true,
                    ajax: crud.format_setting("model_list_url", {model: this.element.data('crud_table'), scope: this.element.data('crud_scope')}),
                    //columns: crud_cols,
                    columns: columns,
                    language: {
                        url: "/vendor/crud/js/plugins/dataTables/lang/russian.json"
                    },
                    rowCallback: rowCallBack


            });
            this.element.on( 'draw.dt', function (e, o) {
                init_checkboxes(columns, this.element);
                crud.trigger('crud.content_loaded', {cont: $(e.target)});
            } );
            this.element.on('dblclick', 'tbody>tr', function (){

                if ($(this).parents('table').first().data('crud_noedit') == '1')
                {
                    return;
                }
                crud.trigger('crud.edit_element', {el:$(this).find('td').first()});
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

})(jQuery, CRUD)